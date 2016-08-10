# HOW TO RUN THE EXAMPLES ? #

You have to edit the file [example.inc.php](example.inc.php "example.inc.php") ; it contains variables that you **MUST** set to your own configuration needs before running the examples :

- **$host** : Host name or IP address of your remote server.
- **port** : Port number of the sshd server on your remote system (usually, 22).
- **$user** : the user you want to connect to on your remote system.
- **$password** : User password (set this variable if you want to run the *example.password.php* script, which uses password-based authentication)
- **$private\_key\_file**, **$public\_key\_file** : Paths to the files containing your private and public ssh keys (set these variables if you want to run the *example.key.php* script, which uses ssh key-based authentication)


# PREREQUISITES #

- Your remote system **MUST** be a Unix system for the examples to run as is
- If you used puttygen to generate your public and private keys, you have to know that the key that is labelled :

			Public key for pasting into OpenSSH authorized_keys file

will not have the correct format for using it as a public key on Unix systems. You will have to go to the Conversions menu and chose the "Export OpenSSH key" option.

- On your remote Unix system, you must add your public key in the *.ssh/authorized_keys* file of your remote user
- And, of course, you must have an *sshd* server up and running on your remote system !





  