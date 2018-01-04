Amazone S3 PHP Command line tool
----------



-----------------------------------------------------------
**Script	    :**	Command line Script to work with Amazone S3
**Written by  :**	Ahmed Rizawan (ahm.rizawan@gmail.com) *2014 feb 10*
**Script Ver  :**	0.0.1_aplha
**AWS SDK     :**	2.5

----------
Usage Examples
--------------------------------------------------------------------------------
	Create a New Bucket				-> 	php aisS3client.php create:bucket "Bucket name"
	Upload a File					-> 	php aisS3client.php upload:normal "Bucket name" "Folder/File Name on Bucket" "File Path" "ACL"
	Upload big File					-> 	php aisS3client.php upload:big "Bucket name" "Folder/File Name on Bucket" "File Path" "ACL"
	Upload Directory				-> 	php aisS3client.php upload:folder "Bucket name" "Path"
	List All Object in Bukt			-> 	php aisS3client.php list:objects "Bucket name"
	Download Object Binary			-> 	php aisS3client.php download:data "Bucket name" "File name"
	Saving an Object as File		->	php aisS3client.php download:file "Bucket name" "File name" "Save Location" "Save file name"
	Get an Object URL				->	php aisS3client.php url:normal "Bucket name" "File name"
	Convert Public & Gen URL		->	php aisS3client.php url:makesimple "Bucket name" "File name"
	Get an Object URL(Limited)		->	php aisS3client.php url:limited "Bucket name" "File name" "Time"
	Set an Object Public			->  php aisS3client.php objectacl:public "Bucket name" "File name"
	Set an Object Private			->  php aisS3client.php objectacl:private "Bucket name" "File name"
	Chk if Bucket name is valid		->	php aisS3client.php chk:bucketname "Bucket Name"
	Chk if File exists				->	php aisS3client.php chk:object "Bucket Name" "File name"
	Delete an Object				->  php aisS3client.php remove:object "Bucket Name" "File name"
	Delete a Bucket					->  php aisS3client.php remove:bucket "Bucket Name"
