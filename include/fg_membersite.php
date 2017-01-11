<?PHP
/*
 Based on 
 *  http://www.html-form-guide.com/php-form/php-registration-form.html
    http://www.html-form-guide.com/php-form/php-login-form.html

*/
require_once("class.phpmailer.php");
require_once("formvalidator.php");

class BCFVolunteers
{
    var $admin_email;
    var $sitename;
    var $from_address;
    var $from_name;
    var $username;
    var $pwd;
    var $database;
    var $tablename;
    var $connection;
    var $rand_key;
    
    var $error_message;
    
    //-----Initialization -------
    function BCFVolunteers()
    {
        $this->rand_key = '0iQx5oBk66oVZep';
    }
    
    function InitDB($host,$uname,$pwd,$database,$tablename,$volunteertable)
    {
        $this->db_host  = $host;
        $this->username = $uname;
        $this->pwd  = $pwd;
        $this->database  = $database;
        $this->tablename = $tablename;
        $this->volunteerstablename = $volunteertable;
    }
    
    function SetAdminEmail($email)
    {
        $this->admin_email = $email;
    }
    
    function SetFromName($from_name)
    {
        $this->from_name = $from_name;
    }
    
    function SetWebsiteName($sitename)
    {
        $this->sitename = $sitename;
    }
    
    function SetRandomKey($key)
    {
        $this->rand_key = $key;
    }
    
    //-------Main Operations ----------------------
    function RegisterUser()
    {
        if(!isset($_POST['submitted']))
        {
           return false;
        }
        
        $formvars = array();
        
        if(!$this->ValidateRegistrationSubmission())
        {
            return false;
        }
        
        $this->CollectRegistrationSubmission($formvars);
        
        if(!$this->SaveToDatabase($formvars))
        {
            return false;
        }
        $confirm_url = $this->SendUserConfirmationEmail($formvars);
        if(!$confirm_url)
        {
            return false;
        }

      //  $this->SendAdminIntimationEmail($formvars);
        
        return $confirm_url;
    }

    function ConfirmUser()
    {
        if(empty($_GET['code'])||strlen($_GET['code'])<=10)
        {
            $this->HandleError("Please provide the confirm code");
            return false;
        }
        $user_rec = array();
        if(!$this->UpdateDBRecForConfirmation($user_rec))
        {
            return false;
        }
        
        $this->SendUserWelcomeEmail($user_rec);
        
        $this->SendAdminIntimationOnRegComplete($user_rec);
        
        return true;
    }    
    
    function Login()
    {
        if(empty($_POST['username']))
        {
            $this->HandleError("UserName is empty!");
            return false;
        }
        
        if(empty($_POST['password']))
        {
            $this->HandleError("Password is empty!");
            return false;
        }
        
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        if(!isset($_SESSION)){ session_start(); }
        if(!$this->CheckLoginInDB($username,$password))
        {
            return false;
        }
        
        $_SESSION[$this->GetLoginSessionVar()] = $username;
        
        return true;
    }
    
    function CheckLogin()
    {
         if(!isset($_SESSION)){ session_start(); }

         $sessionvar = $this->GetLoginSessionVar();
         
         if(empty($_SESSION[$sessionvar]))
         {
            return false;
         }
         return true;
    }
    
    function UserFullName()
    {
        return isset($_SESSION['name_of_user'])?$_SESSION['name_of_user']:'';
    }
    
    function UserEmail()
    {
        return isset($_SESSION['email_of_user'])?$_SESSION['email_of_user']:'';
    }
    
    function LogOut()
    {
        session_start();
        
        $sessionvar = $this->GetLoginSessionVar();
        
        $_SESSION[$sessionvar]=NULL;
        
        unset($_SESSION[$sessionvar]);
    }
    
    function EmailResetPasswordLink()
    {
        if(empty($_POST['email']))
        {
            $this->HandleError("Email is empty!");
            return false;
        }
        $user_rec = array();
        if(false === $this->GetUserFromEmail($_POST['email'], $user_rec))
        {
            return false;
        }
        if(false === $this->SendResetPasswordLink($user_rec))
        {
            return false;
        }
        return true;
    }
    
    function ResetPassword()
    {
        if(empty($_GET['email']))
        {
            $this->HandleError("Email is empty!");
            return false;
        }
        if(empty($_GET['code']))
        {
            $this->HandleError("reset code is empty!");
            return false;
        }
        $email = trim($_GET['email']);
        $code = trim($_GET['code']);
        
        if($this->GetResetPasswordCode($email) != $code)
        {
            $this->HandleError("Bad reset code!");
            return false;
        }
        
        $user_rec = array();
        if(!$this->GetUserFromEmail($email,$user_rec))
        {
            return false;
        }
        
        $new_password = $this->ResetUserPasswordInDB($user_rec);
        if(false === $new_password || empty($new_password))
        {
            $this->HandleError("Error updating new password");
            return false;
        }
        
        if(false == $this->SendNewPassword($user_rec,$new_password))
        {
            $this->HandleError("Error sending new password");
            return false;
        }
        return true;
    }
    
    function ChangePassword()
    {
        if(!$this->CheckLogin())
        {
            $this->HandleError("Not logged in!");
            return false;
        }
        
        if(empty($_POST['oldpwd']))
        {
            $this->HandleError("Old password is empty!");
            return false;
        }
        if(empty($_POST['newpwd']))
        {
            $this->HandleError("New password is empty!");
            return false;
        }
        
        $user_rec = array();
        if(!$this->GetUserFromEmail($this->UserEmail(),$user_rec))
        {
            return false;
        }
        
        $pwd = trim($_POST['oldpwd']);
        
        if($user_rec['password'] != md5($pwd))
        {
            $this->HandleError("The old password does not match!");
            return false;
        }
        $newpwd = trim($_POST['newpwd']);
        
        if(!$this->ChangePasswordInDB($user_rec, $newpwd))
        {
            return false;
        }
        return true;
    }
    
    //-------Public Helper functions -------------
    function GetSelfScript()
    {
        return htmlentities($_SERVER['PHP_SELF']);
    }    
    
    function SafeDisplay($value_name)
    {
        if(empty($_POST[$value_name]))
        {
            return'';
        }
        return htmlentities($_POST[$value_name]);
    }
    
    function RedirectToURL($url)
    {
        header("Location: $url");
        exit;
    }
    
    function GetSpamTrapInputName()
    {
        return 'sp'.md5('KHGdnbvsgst'.$this->rand_key);
    }
    
    function GetErrorMessage()
    {
        if(empty($this->error_message))
        {
            return '';
        }
        $errormsg = nl2br(htmlentities($this->error_message));
        return $errormsg;
    }    
    //-------Private Helper functions-----------
    
    function HandleError($err)
    {
        $this->error_message .= $err."\r\n";
    }
    
    function HandleDBError($err)
    {
        $this->HandleError($err."\r\n mysqlerror:".mysqli_error());
    }
    
    function GetFromAddress()
    {
        if(!empty($this->from_address))
        {
            return $this->from_address;
        }

        $host = $_SERVER['SERVER_NAME'];

        $from ="camra@$host";
        return $from;
    }
    function GetFromName()
    {
        if(!empty($this->from_name))
        {
            return $this->from_name;
        }

        $from ="Bedford Beer & Cider Festival";
        return $from;
    }
    
    function GetLoginSessionVar()
    {
        $retvar = md5($this->rand_key);
        $retvar = 'usr_'.substr($retvar,0,10);
        return $retvar;
    }
    
    function CheckLoginInDB($username,$password)
    {
        if(!$this->DBLogin())
        {
            $this->HandleError("Database login failed!");
            return false;
        }          
        $username = $this->SanitizeForSQL($username);
        $pwdmd5 = md5($password);
        $qry = "Select name, email from $this->tablename where username='$username' and password='$pwdmd5' and confirmcode='y'";
        
        $result = mysqli_query($this->connection,$qry);
        
        if(!$result || mysqli_num_rows($result) <= 0)
        {
            $this->HandleError("Error logging in. The username or password does not match");
            return false;
        }
        
        $row = mysqli_fetch_assoc($result);
        
        
        $_SESSION['name_of_user']  = $row['name'];
        $_SESSION['email_of_user'] = $row['email'];
        
        return true;
    }
    
    function UpdateDBRecForConfirmation(&$user_rec)
    {
        if(!$this->DBLogin())
        {
            $this->HandleError("Database login failed!");
            return false;
        }   
        $confirmcode = $this->SanitizeForSQL($_GET['code']);
        
        $result = mysqli_query($this->connection,"Select name, email from $this->tablename where confirmcode='$confirmcode'");   
        if(!$result || mysqli_num_rows($result) <= 0)
        {
            $this->HandleError("Wrong confirm code.");
            return false;
        }
        $row = mysqli_fetch_assoc($result);
        $user_rec['name'] = $row['name'];
        $user_rec['email']= $row['email'];
        
        $qry = "Update $this->tablename Set confirmcode='y' Where  confirmcode='$confirmcode'";
        
        if(!mysqli_query($this->connection,$qry))
        {
            $this->HandleDBError("Error inserting data to the table\nquery:$qry");
            return false;
        }      
        return true;
    }
    
    function ResetUserPasswordInDB($user_rec)
    {
        $new_password = substr(md5(uniqid()),0,10);
        
        if(false == $this->ChangePasswordInDB($user_rec,$new_password))
        {
            return false;
        }
        return $new_password;
    }
    
    function ChangePasswordInDB($user_rec, $newpwd)
    {
        $newpwd = $this->SanitizeForSQL($newpwd);
        
        $qry = "Update $this->tablename Set password='".md5($newpwd)."' Where  id_user=".$user_rec['id_user']."";
        
        if(!mysqli_query($this->connection,$qry))
        {
            $this->HandleDBError("Error updating the password \nquery:$qry");
            return false;
        }     
        return true;
    }
    
    
    
    function GetUserFromEmail($email,&$user_rec)
    {
        if(!$this->DBLogin())
        {
            $this->HandleError("Database login failed!");
            return false;
        }   
        $email = $this->SanitizeForSQL($email);
        
        $result = mysqli_query($this->connection,"Select * from $this->tablename where email='$email'");  

        if(!$result || mysqli_num_rows($result) <= 0)
        {
            $this->HandleError("There is no user with email: $email");
            return false;
        }
        $user_rec = mysqli_fetch_assoc($result);

        
        return true;
    }
    
    function SendUserWelcomeEmail(&$user_rec)
    {  /*   DISABLED FOR THE MOMENT
        $mailer = new PHPMailer();
        $mailer->CharSet = 'utf-8';
        $mailer->AddAddress($user_rec['email'],$user_rec['name']);
        $mailer->Subject = "Welcome to ".$this->sitename;
        $mailer->From = $this->GetFromAddress(); 
        $mailer->FromName = $this->GetFromName();       
        $mailer->Body ="Hello ".$user_rec['name']."\r\n\r\n".
        "Welcome! Your registration  with ".$this->sitename." is completed.\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;

        if(!$mailer->Send())
        {
            $this->HandleError("Failed sending user welcome email.");
            return false;
        }  */
        return true;
    }
    
    function SendVolunteerWelcomeEmail(&$volunteer)
    {  
	$mail = new PHPMailer();

	# send via SMTP
	$mail->IsSendmail();
	$mail->Host = 'mail.scruntlehawk.com';
        $mail->CharSet = 'utf-8';
	$mail->FromName	= 'Bedford Beer & Cider Festival';  
	$mail->From	= 'camra@scruntlehawk.com'; 
	$mail->Sender =	'camra@scruntlehawk.com'; // the envelope sender(server) of the email for undeliverable mail
	
	$mail->AddAddress('mike@scruntlehawk.com','Bedford Beer Festival Office Manager'); 
#        $mail->AddAddress('beerfestival@northbedscamra.org.uk','Bedford Beer Festival Organiser'); 
        if ($volunteer['choice1'] == "Steward/Admission" || $volunteer['choice2'] == "Steward/Admission") {
            $mail->AddAddress('unikorn1312@yahoo.co.uk','Katharine Lilley'); 
            $mail->AddAddress('kitdavies4@yahoo.com','Kit Davies');
        }
        
        $email = filter_var($volunteer['email'], FILTER_SANITIZE_EMAIL);

	// Validate e-mail
	if (!filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $mail->AddAddress($email,'Bedford Beer Festival Volunteer');
        }
 
	$mail->WordWrap = 50; 
	$mail->IsHTML(true); 

	$mail->Subject  =  $volunteer['name1']." ".$volunteer['name2']. " has volunteered"; 
	$mail->Body     =  "The following Volunteer Form has been received and stored in the Bedford Beer and Cider Festival Database :-<br><br>"; 	
       
	foreach($volunteer as $key=>$field)
	{	
            switch ($key) 
            {
                case 'Sun11': 
                    $label = "Sunday Morning Set-up ";
                    break;
                case 'Mon1':
                    $label = "Monday Morning Set-up ";
                    break; 
                case 'Tues1':
                    $label = "Tuesday Morning Set-up ";
                    break; 
                case 'Wed1':
                    $label = "Wednesday Morning Set-up ";
                    break; 
                case 'Sun21':
                    $label = "Sunday Morning Take-down ";
                    break; 
                case 'Sun12':
                    $label = "Sunday Afternoon Set-up ";
                    break; 
                case 'Mon2':
                    $label = "Monday Afternoon Set-up ";
                    break; 
                case 'Tues2':
                    $label = "Tuesday Afternoon Set-up ";
                    break; 
                case 'Sun22':
                    $label = "Sunday Afternoon Take-down ";
                    break; 
                case 'Thurs1':
                    $label = "Thursday Lunchtime ";
                    break; 
                case 'Fri1':
                    $label = "Friday Lunchtime ";
                    break; 
                case 'Sat1':
                    $label = "Saturday Lunchtime ";
                    break; 
                case 'Wed2': 
                    $label = "Wednesday p.m. ";
                    break;
                case 'Thurs2':
                    $label = "Thursday p.m. ";
                    break; 
                case 'Fri2':
                    $label = "Friday p.m. ";
                    break; 
                case 'Sat2':
                    $label = "Saturday p.m. ";
                    break; 
                case 'Wed3':
                    $label = "Wednesday Evening ";
                    break; 
                case 'Thurs3':
                    $label = "Thursday Evening ";
                    break; 
                case 'Fri3':
                    $label = "Friday Evening ";
                    break; 
                case 'Sat3':
                    $label = "Saturday Evening ";
                    break;
                default:
		    $label = $key;
            }
	
            if($field != "N" && $field != "")	
            {
                $mail->Body .= $label." - ".$field."<br />"		;
            }
	}
	
        $mail->Body .= "<br /><br />Regards,<br />".
        "The Bedford Beer & Cider Festival Management Team<br />";
        
	if(!$mail->Send())
        {
            return false;
        }
	else
        {
            return true;
        }
    }
    
    function SendAdminIntimationOnRegComplete(&$user_rec)
    {
        if(empty($this->admin_email))
        {
            return false;
        }
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($this->admin_email);
        
        $mailer->Subject = "Registration Completed: ".$user_rec['name'];

        $mailer->From = $this->GetFromAddress();         
        $mailer->FromName = $this->GetFromName();
        $mailer->Body ="A new user registered at ".$this->sitename."\r\n".
        "Name: ".$user_rec['name']."\r\n".
        "Email address: ".$user_rec['email']."\r\n";
        
        if(!$mailer->Send())
        {
            return false;
        }
        return true;
    }
    
    function GetResetPasswordCode($email)
    {
       return substr(md5($email.$this->sitename.$this->rand_key),0,10);
    }
    
    function SendResetPasswordLink($user_rec)
    {
        $email = $user_rec['email'];       
        $mailer = new PHPMailer();        
        $mailer->CharSet = 'utf-8'; 
        $mailer->AddAddress($email,$user_rec['name']);
        $mailer->Subject = "Your reset password request at ".$this->sitename;
        $mailer->From = $this->GetFromAddress();
        $mailer->FromName = $this->GetFromName();
        $link = $this->GetAbsoluteURLFolder().
                '/resetpwd.php?email='.
                urlencode($email).'&code='.
                urlencode($this->GetResetPasswordCode($email));

        $mailer->Body ="Hello ".$user_rec['name']."\r\n\r\n".
        "There was a request to reset your password at ".$this->sitename."\r\n".
        "Please click the link below to complete the request: \r\n".$link."\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        
        if(!$mailer->Send())
        {
            return false;
        }
        return true;
    }
    
    function SendNewPassword($user_rec, $new_password)
    {
        $email = $user_rec['email'];
        
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($email,$user_rec['name']);
        
        $mailer->Subject = "Your new password for ".$this->sitename;

        $mailer->From = $this->GetFromAddress();
        $mailer->FromName = $this->GetFromName();
        
        $mailer->Body ="Hello ".$user_rec['name']."\r\n\r\n".
        "Your password has been reset successfully. ".
        "Here is your updated login:\r\n".
        "username:".$user_rec['username']."\r\n".
        "password:$new_password\r\n".
        "\r\n".
        "Login here: ".$this->GetAbsoluteURLFolder()."/login.php\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        
        if(!$mailer->Send())
        {
            return false;
        }
        return true;
    }    
    
    function ValidateRegistrationSubmission()
    {
        //This is a hidden input field. Humans won't fill this field.
        if(!empty($_POST[$this->GetSpamTrapInputName()]) )
        {
            //The proper error is not given intentionally
            $this->HandleError("Automated submission prevention: case 2 failed");
            return false;
        }
        
        $validator = new FormValidator();
        $validator->addValidation("name","req","Please fill in Name");
        $validator->addValidation("email","email","The input for Email should be a valid email value");
        $validator->addValidation("email","req","Please fill in Email");
        $validator->addValidation("username","req","Please fill in UserName");
        $validator->addValidation("password","req","Please fill in Password");

        
        if(!$validator->ValidateForm())
        {
            $error='';
            $error_hash = $validator->GetErrors();
            foreach($error_hash as $inpname => $inp_err)
            {
                $error .= $inpname.':'.$inp_err."\n";
            }
            $this->HandleError($error);
            return false;
        }        
        return true;
    }
    
    function CollectRegistrationSubmission(&$formvars)
    {
        $formvars['name'] = $this->Sanitize($_POST['name']);
        $formvars['email'] = $this->Sanitize($_POST['email']);
        $formvars['username'] = $this->Sanitize($_POST['username']);
        $formvars['password'] = $this->Sanitize($_POST['password']);
    }
    
    function SendUserConfirmationEmail(&$formvars)
    {
  /*      $mailer = new PHPMailer();
        $mailer->CharSet = 'utf-8';
        $mailer->AddAddress($formvars['email'],$formvars['name']);
        $mailer->Subject = "Your registration with ".$this->sitename;
        $mailer->From = $this->GetFromAddress();  */      
        $confirmcode = $formvars['confirmcode'];
        $confirm_url = $this->GetAbsoluteURLFolder().'/confirmreg.php?code='.$confirmcode;
    /*    $mailer->Body ="Hello ".$formvars['name']."\r\n\r\n".
        "Thanks for your registration with ".$this->sitename."\r\n".
        "Please click the link below to confirm your registration.\r\n".
        "$confirm_url\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        if(!$mailer->Send())
        {
            $this->HandleError("Failed sending registration confirmation email.");
            return false;
        }*/
        return $confirm_url;  
    }

    function GetAbsoluteURLFolder()
    {
        $scriptFolder = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://';
        $scriptFolder .= $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        return $scriptFolder;
    }
    
    function SendAdminIntimationEmail(&$formvars)
    {
        if(empty($this->admin_email))
        {
            return false;
        }
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($this->admin_email);
        
        $mailer->Subject = "New registration: ".$formvars['name'];

        $mailer->From = $this->GetFromAddress();         
        $mailer->FromName = $this->GetFromName();
        $mailer->Body ="A new user registered at ".$this->sitename."\r\n".
        "Name: ".$formvars['name']."\r\n".
        "Email address: ".$formvars['email']."\r\n".
        "UserName: ".$formvars['username'];
        
        if(!$mailer->Send())
        {
            return false;
        }
        return true;
    }
    
    function SaveToDatabase(&$formvars)
    {
        if(!$this->DBLogin())
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        if(!$this->Ensuretable())
        {
            return false;
        }
        if(!$this->EnsureVolunteerstable())
        {
            return false;
        }
        if(!$this->IsFieldUnique($formvars,'email'))
        {
            $this->HandleError("This email is already registered");
            return false;
        }
        
        if(!$this->IsFieldUnique($formvars,'username'))
        {
            $this->HandleError("This UserName is already used. Please try another username");
            return false;
        }        
        if(!$this->InsertIntoDB($formvars))
        {
            $this->HandleError("Inserting to Database failed!");
            return false;
        }
        return true;
    }
    
    function IsFieldUnique($formvars,$fieldname)
    {
        $field_val = $this->SanitizeForSQL($formvars[$fieldname]);
        $qry = "select username from $this->tablename where $fieldname='".$field_val."'";
        $result = mysqli_query($this->connection,$qry);  
        if($result && mysqli_num_rows($result) > 0)
        {
            return false;
        }
        return true;
    }
    
    function DBLogin()
    {

        $this->connection = mysqli_connect($this->db_host,$this->username,$this->pwd);

        if(!$this->connection)
        {   
            $this->HandleDBError("Database Login failed! Please make sure that the DB login credentials provided are correct");
            return false;
        }
        if(!mysqli_select_db($this->connection, $this->database))
        {
            $this->HandleDBError('Failed to select database: '.$this->database.' Please make sure that the database name provided is correct');
            return false;
        }
        if(!mysqli_query($this->connection,"SET NAMES 'UTF8'"))
        {
            $this->HandleDBError('Error setting utf8 encoding');
            return false;
        }
        return true;
    }    
    
    function Ensuretable()
    {
        $result = mysqli_query($this->connection,"SHOW COLUMNS FROM $this->tablename");   
        if(!$result || mysqli_num_rows($result) <= 0)
        {
            return $this->CreateTable();
        }
        return true;
    }
    
    function CreateTable()
    {
        $qry = "Create Table $this->tablename (".
                "id_user INT NOT NULL AUTO_INCREMENT ,".
                "name VARCHAR( 128 ) NOT NULL ,".
                "email VARCHAR( 64 ) NOT NULL ,".
                "phone_number VARCHAR( 16 ) NOT NULL ,".
                "username VARCHAR( 16 ) NOT NULL ,".
                "password VARCHAR( 32 ) NOT NULL ,".
                "confirmcode VARCHAR(32) ,".
                "PRIMARY KEY ( id_user )".
                ")";
                
        if(!mysqli_query($this->connection,$qry))
        {
            $this->HandleDBError("Error creating the table \nquery was\n $qry");
            return false;
        }
        return true;
    }
    
    function EnsureVolunteerstable()
    {
        $result = mysqli_query($this->connection,"SHOW COLUMNS FROM $this->tablename");   
        if(!$result || mysqli_num_rows($result) <= 0)
        {
            return $this->CreateVolunteersTable();
        }
        return true;
    }
    
    function CreateVolunteersTable()
    {
        $qry = "Create Table $this->volunteerstablename (".
            "id int(11) NOT NULL, ".
            "name1 varchar(50) DEFAULT NULL, ".
            "name2 varchar(50) DEFAULT NULL, ".
            "phone varchar(50) DEFAULT NULL,".
            "address1 varchar(50) DEFAULT NULL, ".
            "address2 varchar(50) DEFAULT NULL, ".
            "town varchar(50) DEFAULT NULL, ".
            "county varchar(50) DEFAULT NULL, ".
            "postcode varchar(15) DEFAULT NULL, ".
            "email varchar(90) DEFAULT NULL, ".
            "memno varchar(15) DEFAULT NULL, ".
            "Sun11 enum('Y','N') DEFAULT 'N' COMMENT 'Sunday a.m. set-up', ".
            "Sun12 enum('Y','N') DEFAULT 'N' COMMENT 'Sunday p.m. set-up', ".
            "Mon1 enum('Y','N') DEFAULT 'N' COMMENT 'Monday Lunchtime', ".
            "Mon2 enum('Y','N') DEFAULT 'N' COMMENT 'Monday p.m.', ".
            "Tues1 enum('Y','N') DEFAULT 'N' COMMENT 'Tuesday Lunchtime', ".
            "Tues2 enum('Y','N') DEFAULT 'N' COMMENT 'Tuesday p.m.', ".
            "Wed1 enum('Y','N') DEFAULT 'N' COMMENT 'Wednesday Lunchtime', ".
            "Wed2 enum('Y','N') DEFAULT 'N' COMMENT 'Wednesday p.m.', ".
            "Wed3 enum('Y','N') DEFAULT 'N' COMMENT 'Wednesday evening', ".
            "Thurs1 enum('Y','N') DEFAULT 'N' COMMENT 'Thursday Lunchtime', ".
            "Thurs2 enum('Y','N') DEFAULT 'N' COMMENT 'Thursday p.m.', ".
            "Thurs3 enum('Y','N') DEFAULT 'N' COMMENT 'Thursday evening', ".
            "Fri1 enum('Y','N') DEFAULT 'N' COMMENT 'Friday Lunchtime', ".
            "Fri2 enum('Y','N') DEFAULT 'N' COMMENT 'Friday p.m.', ".
            "Fri3 enum('Y','N') DEFAULT 'N' COMMENT 'Friday evening', ".
            "Sat1 enum('Y','N') DEFAULT 'N' COMMENT 'Saturday Lunchtime', ".
            "Sat2 enum('Y','N') DEFAULT 'N' COMMENT 'Saturday p.m.', ".
            "Sat3 enum('Y','N') DEFAULT 'N' COMMENT 'Saturday evening', ".
            "Sun21 enum('Y','N') DEFAULT 'N' COMMENT 'Sunday a.m. take-down', ".
            "Sun22 enum('Y','N') DEFAULT 'N' COMMENT 'Sunday p.m. take-down', ".
            "choice1 varchar(50) DEFAULT NULL, ".
            "choice2 varchar(50) DEFAULT NULL, ".
            "other varchar(255) DEFAULT NULL, ".
            "date_added timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ".
                ")";
                
        if(!mysqli_query($this->connection,$qry))
        {
            $this->HandleDBError("Error creating the volunteers table \nquery was\n $qry");
            return false;
        }
        return true;
    }
    
    function InsertIntoDB(&$formvars)
    {
    
        $confirmcode = $this->MakeConfirmationMd5($formvars['email']);
        
        $formvars['confirmcode'] = $confirmcode;
        
        $insert_query = 'insert into '.$this->tablename.'(
                name,
                email,
                username,
                password,
                confirmcode
                )
                values
                (
                "' . $this->SanitizeForSQL($formvars['name']) . '",
                "' . $this->SanitizeForSQL($formvars['email']) . '",
                "' . $this->SanitizeForSQL($formvars['username']) . '",
                "' . md5($formvars['password']) . '",
                "' . $confirmcode . '"
                )';      
        if(!mysqli_query($this->connection,$insert_query))
        {
            $this->HandleDBError("Error inserting data to the table\nquery:$insert_query");
            return false;
        }        
        return true;
    }
    function MakeConfirmationMd5($email)
    {
        $randno1 = rand();
        $randno2 = rand();
        return md5($email.$this->rand_key.$randno1.''.$randno2);
    }
    function SanitizeForSQL($str)
    {
        if( function_exists( "mysqli_real_escape_string" ) )
        {
              $ret_str = mysqli_real_escape_string( $this->connection,$str );
        }
        else
        {
              $ret_str = addslashes( $str );
        }
        return $ret_str;
    }
    
 /*
    Sanitize() function removes any potential threat from the
    data submitted. Prevents email injections or any other hacker attempts.
    if $remove_nl is true, newline chracters are removed from the input.
    */
    function Sanitize($str,$remove_nl=true)
    {
        $str = $this->StripSlashes($str);

        if($remove_nl)
        {
            $injections = array('/(\n+)/i',
                '/(\r+)/i',
                '/(\t+)/i',
                '/(%0A+)/i',
                '/(%0D+)/i',
                '/(%08+)/i',
                '/(%09+)/i'
                );
            $str = preg_replace($injections,'',$str);
        }

        return $str;
    }    
    function StripSlashes($str)
    {
        if(get_magic_quotes_gpc())
        {
            $str = stripslashes($str);
        }
        return $str;
    }    
}
?>