# INTRODUCTION #

The **SshSession** class (and all of its related classes) is intended to establish SSH connections to a remote host running an **sshd** server and allow you to execute commands remotely and retrieve their output, or access the remote filesystem using the **SFTP** protocol.

The class relies on the **libssh2** PHP extension.

The following example illustrates a simple usage of this package ; it connects remotely, executes the "ls -al" command by redirectoring its output to file */tmp/ls.out*, then retrieves file contents and display them :

	require_once ( 'Session.phpclass' ) ;

	// Create an instance for host 1.2.3.4 port 22 ; no connection exists at that stage
	$session = new SshSession ( '1.2.3.4', 22 ) ; 		

	// Authenticate using the specified user/password
	$session -> Authenticate ( new SshPasswordAuthentication ( $session, 'myuser', 'mypassword' ) ;

	// Execute the ls command and redirect its output to file /tmp/ls.out
	$status 	=  $session -> Execute ( "ls -al / >tmp/ls.out' ) ;

	// Get a reference object to the remote filesystem
	$fs 		=  $session -> GetFileSystem ( ) ;

	// Retrieve then display /tmp/ls.out file contents
	echo $fs -> file_get_contents ( '/tmp/ls.out' ) ;

	// Remove temporary file
	$fs -> unlink ( '/tmp/ls.out' ) ;

Of course, you can also use authentication using public and private keys defined in external files ; just replace the following code :

	$session -> Authenticate ( new SshPasswordAuthentication ( $session, 'myuser', 'mypassword' ) ;

with :

	$session -> Authenticate ( new SshPublicKeyAuthentication ( $session, 'myuser', 'mypublickey.ppk', 'myprivatekey.ppk' ) ;

## FEATURES ##

The **SshSession** class tries to protect you from some issues found with the **libssh2** PHP extension (especially on Windows) and from some limitations of the SSH protocol :

- Commands can be of arbitrary length ; if commands are longer than the maximum authorized command-line length, they will be transparently put into a temporary file on the remote system before being executed using the *bash* shell. This way, you don't have to worry about command-line length.
- You will always be able to retrieve the status of the latest command that was executed (see the *Execute()* method of the **SshSession** class)
- Once a connection has been established, you will be able to use the **SshFileSystem** object (see the **GetFileSystem()* method of the **SshSession** class) to remotely manipulate files and directories
- At least on Windows systems, the following two cases will bring you an error :
	- Execute a command, then an SFTP operation, then another command
	- Execute two commands consecutively

  The **SshSession** class handles the burden of such situations by automatically disconnecting you then reconnecting before the next operation to be performed, in order to avoid you to have to deal with such errors.

## PREREQUISITES ##

The remote systems you will be accessing will need the following :
- An up-and-running *sshd* server
- A version of PHP with the *libssh2* library installed and configured as an extension library (either in CLI mode or when running with a web server)
- If you want to authenticate using public and private keys, the user on your remote server will need to have your public key in the *authorized_keys* file of the *.ssh* directory located in the home directory for this user.

## WARNING FOR USERS OF PHP > 5.6.27 ##

PHP 5.6.28 broke something (or radically changed something) in the way the ssh2.sftp wrapper parses a file specification. This led to errors when using the opendir() function, for example.

This became "less worse" with PHP 7.0, but resource id strings in path specifications remain unrecognized, unlike in versions <= 5.6.27 (the Ssh Session classes has been fixed to handle
this new case).

PHP 7.1 solved the problem ; however, on Windows platforms, using multiple SFTP sessions within the same script can lead to errors when trying to access existing remote files. 
More annoying is that an access violation is generated at the end of the script in php_libssh2.dll. This problem, which is linked to the current implementation of the libssh2 extension,
is currently under investigation.

## PACKAGE DESCRIPTION ##

This package contains 4 main classes :

- **SshSession** : the main object you will have to instantiate for gaining access to your remote system
- **SshAuthentication** : An abstract base class for authenticating yourself to a remote system ; it has 6 derived classes (see the *SshSession::Authenticate()* method) :
	- **SshPasswordAuthentication** : Used for authenticating yourself using a user/password.
	- **SshPublicKeyAuthentication** : Used for authenticating yourself with a public and private key.
	- **SshKeyBaseAuthentication** : Another way of authentication (not tested).
	- **SshHostBaseAuthentication** : A way to authenticate yourself using public and private keys coming from a third-party host (not tested).
	- **SshAgentAuthentication** : A way to authenticate using an SSH agent and a username (not tested).
	- **SshAuthenticationNone** : Not tested. To tell the truth, I did not completely understand the utility of this authentication method, although I'm sure there is one.

The classes you are likely to use most often will probably be **SshPasswordAuthentication** and **SshPublicKeyAuthentication**.

- **SshFileSystem** : An object that you can retrieve using the *SshSession::GetFileSystem()* method, which will give you access to the remote files and directories.
- **SshConnection** : A class used internally to coordinate all of the above.

# CLASS REFERENCE #

## SshSession class ##


### METHODS ###

#### __construct ( $host\_or\_resource = null, $port = 22, $methods = null, $callbacks = null ) ; ####

Builds an Ssh session, including access to shell and sftp. 

Note that instantiating an *SshSession* object does not establish a connection to the remote system : you must call the *Connect()* method before that.  

The parameters are the following :

- *host\_or\_resource* (string, resource or SshConnection object) : Remote host to connect to. It can have one of the following types :
	- *string* : Host name or ip address that will be supplied to the ssh2_connect() function.
	- *resource* : An already existing ssh connection. Note that in this case, the Host, Port, Methods and Callbacks properties of this SshConnection object will be undefined.
	- *SshConnection object* : Duplicates the parameters of an existing SshConnection object.
- *port* (integer) : Ssh port to be used for the connection. Default port is 22.
- *methods* (array) : Associative array. See the *ssh2_connect()* function help for for a description of its entries.
- *callbacks* (array) : An associative array providing the names of callback functions (see the *ssh2_connect()* function). Note that all the callbacks must be implemented in the *SshConnection class* . This parameter can be used to override existing callbacks in derived classes.

#### $session -> Authenticate ( $auth_object ) ####

Authenticates on an already connected session using the specified *SshAuthentication* class-derived object. 


#### $connection = $session -> Connect ( $host_or_resource = null, $port = 22, $methods = null, $callbacks = null ) ####

Connects to the specified remote host. See the class constructor help for an explanation about the method parameters.

Returns an **SshConnection** object.

#### $status = $session -> Disconnect ( ) ####

Disconnects from an existing SSH session.

Returns true if disconnection was successful, false otherwise (for example, when no connection was established).

#### $status =  $session -> Execute ( $command, &$output = null, $env = null ) ; ####

Executes a command on a remote server.

The parameters are the following :

- *$command* (string) - Command(s) to be executed.
- *$output* (array or callback) - If specified as an array, will receive the output lines generated by the executed command(s).	If null, results will be output (almost) in real time.
- *$env* (array) - Optional associative array that defines environment variable names/values to be used when executing the command.

Returns the status of the last executed command (the $? variable value of the bash/sh shell interpreter).

_NOTES_ :

- At least on Windows systems, getting the output and error output of a remote command execution is a real nightmare ; standard error is not included by default in the stream returned by *ssh2_exec()*, and calling *ssh2_fetch_stream()* to obtain a standard error stream does not solve the problem, either : even if both *stderr* and *stdout* contain data, reading the first will succeed, but reading the second will result in a never ending call, whatever the order of data reads (stdout then stderr, or stderr then stdout).

For those reasons, the *Execute()* method transforms the supplied input commands to the following script :

	bash 2>&1  <<END
		$commands
	END

The drawback is that *stderr* is interspersed with *stdout*, but in the order it has been output.

- Status of the last executed command is returned by appending "echo $?" after the last command.

#### $fs = $session -> GetFileSystem ( ) ; ####

Creates an *SshFileSystem* object (if not already created) to allow remote access to the server files.

#### $session -> Reconnect ( ) ; ####

Disconnects, reconnects then re-authenticates.

Added mainly as a workaround for the *libssh2* bug which prevents ANYTHING to be executed or transferred after a command has been executed (apparently, it concerns only Windows implementations).


#### $status = $session -> IsConnected ( ) ; ####

Returns true if the session object has established a connection to the remote server.

Note that being connected does not necessarily mean being authenticated.

#### $status = $session -> IsAuthenticated ( ) ; ####

Returns true if the connection is opened and a user has been authenticated.

### PROPERTIES ###

#### Connection ####

Returns an *SshConnection* object.

## SshFileSystem class ##

An object of type **SshFileSystem** provides access to a remote file system.

It can be retrieved using the *GetFileSystem()* method of the **SshSession** class, once connected and authenticated (an exception is thrown if no connection has been established or if no user has been authenticated).

Most of the methods of this class mimic their classic PHP counterpart.

### METHODS ###

#### $path = $fs -> SshPath ( $path ) ; ####

Returns a path using the ssh2.sftp:// stream wrapper.

#### $old_cwd = $fs -> chdir ( $path ) ; ####

Changes the current working directory on the remote server and returns the previous one

#### $status = $fs -> chmod ( $remote_file, $mode ) ; ####

Changes the permissions for the specified file on the remote server. See the *chmod()* function for an explanation on the *$mode* parameter.

#### $fs -> closedir ( $resource ) ; #####

Closes a directory opened by the *opendir()* method.

#### $status = $fs -> fclose ( $fp ) ; ####

Closes a file opened by the *fopen()* method.

#### $data = $fs -> fgets ( $fp, $data, $length = null ) ; ####

Reads a line from the specified file opened by the *fopen()* method.

#### $lines = $fs -> file ( $filename, $flags = 0, $context = null ) ####

Gets remote file contents, as an array of lines.

#### $status = $fs -> file\_exists ( $filename ) ; ####

Checks if the specified file exists on the remote host.

#### $fs -> file\_get\_contents ( $filename, $use\_include\_path = false, $context = null, $offset = -1 ) ; ####

Retrieves the contents of a remote file, using the ssh2.sftp wrapper.

Note that the *$maxlen* parameter of the traditional PHP *file\_get\_contents* function is not used, since it always gives empty results.

#### $fs -> file\_put\_contents ( $filename, $data, $flags = 0, $context = null ) ; ####

Replaces the contents of a remote file, using the ssh2.sftp wrapper.

#### $fp = $fs -> fopen ( $remote\_file, $mode, $use\_include\_path = false, $context = false ) ; ####

Opens a file on the remote host.

#### $data = $fs -> passthru ( $fp ) ; ####

Reads an already opened file until the end and returns its data.

#### $status = $fs -> fprintf ( $fp, $format, ... ) ; ####

fprintf() function, on a remote file.

#### $status = $fs -> fputs ( $fp, $data, $length = null ) ; ####

fputs() function, on a remote file.

#### $data = $fs -> fread ( $fp, $length = null ) ; ####

fread() function, on a remote file.

#### $status = $fs -> fseek ( $fp, $offset, $whence = SEEK_SET ) ; ####

Seeks on a remote file.

#### $offset = $fs -> ftell ( $fp ) ####

Returns the current offset of  remote file.

#### $status = $fs -> fwrite ( $fp, $data, $length = null ) ; ####

fwrite() function, on a remote file.

#### $cwd = $fs -> getcwd ( ) ; ####

Returns the current working directory, which is evaluated through the realpath() method upon connection.

#### $status = $fs -> gzclose ( $fp ) ; ####

Closes an opened gzipped file.

#### $fp = $fs -> gzopen ( $remote\_file, $mode, $use\_include\_path = false ) ; ####

Opens a remote gzipped file.

#### $data = $fs -> gzread ( $fp, $length = null ) ; ####

gzread() function, on a remote file.

#### $status = $fs -> is\_dir ( $remote\_file ) ; ####

Checks if the specified file exists on the remote host and is a directory.

#### $status = $fs -> is\_file ( $remote\_file ) ; ####

Checks if the specified file exists on the remote host and is a plain file.

#### $status = $fs -> lstat ( $remote\_file ) ; ####

Stats a remote symbolic link target.

#### $status = $fs -> mkdir ( $remote\_directory, $mode = 0755, $recursive = false ) ; ####

Creates a directory on the remote host.

#### $rs = $fs -> opendir ( $path ) ; ####

Opens a remote directory.

#### $result = $fs -> readdir ( $resource ) ; ####

Reads the next directory entry.

#### $path = $fs -> readlink ( $remote\_file ) ; ####

Returns the target path of a symbolic link on the remote host.

#### $path = $fs -> realpath ( $remote\_file ) ; ####

Returns the real path of a file on the remote host.

#### $status = $fs -> receive ( $remote\_file, $local\_file ) ; ####

Receives a file from the remote server.

#### $status = $fs -> rename ( $old\_name, $new\_name ) ; ####

Renames a file on the remote host.

#### $status = $fs -> rewind ( $fp ) ; ####

Rewinds an already opened file on the remote server.

#### $status = $fs -> rmdir ( $remote\_directory ) ; ####

Removes a directory from the remote host.

#### $status = $fs -> send ( $local\_file, $remote\_file, $mode = 0644 ) ; ####

Sends a file to the remote server.

#### $result = $fs -> stat ( $remote_file ) ; ####

Stats a remote file.

#### $status = $fs -> symlink ( $file, $target ) ; ####

Creates a symbolic link.

#### $status = $fs -> unlink ( $remote\_file ) ; ####

Deletes a file from the remote host.

### PROPERTIES ###

#### Session ####

Returns the **SshSession** parent object for this file system.

#### SftpSession ####

A resource returned by the PHP *ssh2\_sftp()* function.

#### Cwd ####

Current working directory on the remote server.

## AUTHENTICATION CLASSES ##

An object of a type belonging to one of the classes derived from **SshAuthentication** needs to be supplied to the *Connect()* method. This section describes the constructors of these various classes.

### SshPasswordAuthentication class ###

Use this class when you need to authenticate using a user and password combination ; the constructor has the following form :

	__construct ( $session, $username, $password ) ;

Where :

- *$session* is an **SshSession** object you already instantiated
- *$user* and *password* are your user/password strings.

### SshPublicKeyAuthentication class ###

Use this class when you need to authenticate using public/private keys ; the constructor has the following form :

	__construct ( $session, $username, $public_key_file, $private_key_file, $passphrase = null ) 

The parameters are the following :

- *$session* : An object of type **SshSession**
- *username* : Username to be used for authentication.
- *public\_key\_file* : File containing your public key.
- *private\_key\_file*: File containing your private key.
- *$passphrase* : If your secret key has a passphrase, then this is the right place to specify it.

This class has not been tested yet.

### SshHostBasedAuthentication ###

Use this class when you need to authenticate using public/private keys that are located on a third-party server.

The constructor has the following form :

	__construct ( $session, $username, $host, $public_key_file, $private_key_file, $passphrase = null ) ;

### SshAgentAuthentication ###

This class has not been tested yet.

The constructor has the following form :

	__construct ( $session, $username ) ;

### SshAuthenticationNone ###

This class has not been tested yet.

The constructor has the following form :

	__construct ( $session, $username ) ;
