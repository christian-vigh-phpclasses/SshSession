<?php
	/****************************************************************************************************

		This example script demonstrates the use of the SshSession class for accessing remote systems
		using password authentication.

		WARNING :
			You will have to supply the appropriate values for the following variables (defined
			in file example.inc.php) :
			- $host		:  remote host name or ip address
			- $port		:  ssh port on the remote host
			- $user		:  username used for the connection
			- $password	:  user password

		WARNING 2 :
			This example works only for accessing remote Unix systems.

		This example performs the following :
		1) Connect to a remote system using ssh password authentication
		2) Create a directory named "/tmp/sshexample" on the remote server
		3) Perform an "ls -al /usr" command and show its result
		4) Perform again an "ls -al" command on the root directory, redirecting its output to file
		   "/tmp/sshexample/ls.out"
		5) Rename "/tmp/sshexample/ls.out" to ""/tmp/sshexample/ls.txt"
		6) Get the contents of file ""/tmp/sshexample/ls.txt" and display its contents
		7) Remove file "/tmp/sshexample/ls.txt"
		8) Remove directory "/tmp/sshexample" (for the sake of your own security, I do not include any
		   example regarding recursive directory deletion).

	 ****************************************************************************************************/

	 require_once ( 'Session.phpclass' ) ;

	 require ( 'example.inc.php' ) ;			// Authentication data

	 if  ( php_sapi_name ( )  !=  'cli' )
		echo ( '<pre>' ) ;

	 // 1) Connect to a remote system using ssh password authentication
	 echo ( '1) Connecting to your system...' ) ;
		 $session	=  new SshSession ( $host, $port ) ;
		 $session -> Connect ( ) ;									// Connection is not automatic
		 $session -> Authenticate ( new SshPasswordAuthentication ( $session, $user, $password ) ) ;	// You need to be connected before authentication
		 $fs		=  $session -> GetFileSystem ( ) ;						// Retrieve access to the remote file system
	 echo ( "done\n" ) ;

	 // 2) Create a directory named "/tmp/sshexample" on the remote server
	 echo ( "2) Creating remote directory $tmpdir..." ) ;
		 $tmpdir	=  '/tmp/sshexample' ;

		 if  ( ! $fs -> is_dir ( $tmpdir ) )
			$fs -> mkdir ( $tmpdir ) ;
	 echo ( "done\n" ) ;

	// 3) Perform an "ls -al" command and show its result
	echo ( "3) Listing contents of the /usr directory :\n" ) ;
		// If you want to retrieve the output contents of the command in an array, you need to initialize the $output variable
		// to a non-null value.
		// If you want the Execute() method to simply display the output contents, remove this initialization
		$output = '' ;
		$status		=  $session -> Execute ( "ls -al /usr", $output ) ;
	echo ( implode ( "\n", $output ) . "\n" ) ;
	echo ( "--- Command status = $status\n" ) ;

	// 4) Perform again an "ls -al" command on the root directory, redirecting its output to file "/tmp/sshexample/ls.out"
	echo ( "4) Listing contents of the /usr directory, redirecting output to file $tmpdir/ls.out..." ) ;
		$status		=  $session -> Execute ( "ls -al /usr >$tmpdir/ls.out" ) ;
	echo ( "done (status = $status)\n" ) ;

	// 5) Rename "/tmp/sshexample/ls.out" to ""/tmp/sshexample/ls.txt"
	echo ( "5) Renaming file $tmpdir/ls.out to $tmpdir/ls.txt..." ) ;
		$status		=  $session -> Execute ( "mv $tmpdir/ls.out $tmpdir/ls.txt" ) ;
	echo ( "done (status = $status)\n" ) ;

	// 6) Get the contents of file ""/tmp/sshexample/ls.txt" and display its contents
	echo ( "6) Displaying contents of file $tmpdir/ls.txt :\n" ) ;
		$output		=  $fs -> file_get_contents ( "$tmpdir/ls.txt" ) ;
	echo ( $output ) ;

	// 7) Remove file "/tmp/sshexample/ls.txt"
	echo ( "7) Removing file $tmpdir/ls.txt..." ) ;
		$status		=  $fs -> unlink ( "$tmpdir/ls.txt" ) ;
	echo ( "done (status = $status)\n" ) ;

	// 8) Remove directory "/tmp/sshexample"
	echo ( "8) Removing directory $tmpdir..." ) ;
		$status		=  $fs -> rmdir ( $tmpdir ) ;
	echo ( "done (status = $status)\n" ) ;

	echo ( "*** end of example script ***" ) ;