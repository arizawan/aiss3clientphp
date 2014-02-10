<?php
/*	#	//	Amazone S3 PHP Command line tool
	#	-----------------------------------------------------------
	#	Script		:	Command line Script to work with Amazone S3
	#	Written by	:	Ahmed Rizawan (ahm.rizawan@gmail.com)
	#	Script Ver	:	0.0.1_aplha
	#	AWS SDK		:	2.5
	
	#	Structure	:	aiss3 	/
								/ 	aisS3client.php	* Main Interpreter Script
								/	composer.json	* Composer File
								/	composer.lock	* Composer Lock File
								/	vendor			* AWS SDK Folder
								/	files/			* File Download Folder
								/	localdir/		* File Upload folder
								
	# 	Modify What ever you want :)
*/
					
/*
	// Usage Examples -----------------------------------------------------------------
	// --------------------------------------------------------------------------------
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
*/

// Auto Load Things i need
require 'vendor/autoload.php';

// Define Things i will use
use Aws\Common\Aws;
use Aws\Common\Enum\Size;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\Model\ClearBucket;

// Static Variables
$aws_access_key			=	'';						//	AWS Access key
$aws_access_security	=	'';						//	AWS Security Key
$aws_default_buket		=	'aisaventltd-cdn';		//	Your Default Bucket
$aws_default_region		=	'ap-southeast-1';		//	Your Default Region
$aws_default_scema		=	'http';					//	Default Protocol Schema
$aws_default_uploadfrom	=	'localdir';				//	File Upload from Directory

// Instantiate the AWS client with your AWS credentials
$aws = Aws::factory(array(
    'key'    => $aws_access_key,
    'secret' => $aws_access_security,
	'region' => $aws_default_region,
	'scheme' => $aws_default_scema,
));

// Define S3client Object
$s3Client 	= 	$aws->get('s3');

// Argument Identifier
	
	// Little bit Validation
	if( count($argv) <= 2 ) {
		echo "Dude! your commands don't seems like they are ok!";
		die();
	}
	
	if( !$aws_access_key || !$aws_access_security) {
		echo "Sanp! please chk your AWS Secrets!";
		die();
	}
	
	
	// Argument Classifier
	$mainCommand	=	explode(":", $argv[1]);	// Get main Command Identifier
	$mainDirective	=	$mainCommand[0];		// Main Directive
	$subDirective	=	$mainCommand[1];		// Sub Directive
	
	// Switching into Options
	switch ($mainDirective) {
		# -- Create ---
		case 'create':
			switch ($subDirective) {
				case 'bucket':
					// ----------------------------------------------------------------------
					// Create Bucket
					// Ex. php aisS3client.php create:bucket aisavent-backups
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					try {
						$result =  $s3Client->createBucket(array(
							'Bucket'             => $buketname,
							'LocationConstraint' => \Aws\Common\Enum\Region::AP_SOUTHEAST_1
						));
						$s3Client->waitUntilBucketExists(array('Bucket' => $buketname));
						echo "Bucket Created : ".$buketname;
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
		# -- List ---
		case 'list':
			switch ($subDirective) {
				case 'objects':
					// ----------------------------------------------------------------------
					// List Objects of a Bucket
					// Ex. php aisS3client.php list:objects aisavent-backups
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					try {
						$iterator = $s3Client->getIterator('ListObjects', array(
							'Bucket' => $buketname
						));
						echo "Listing Objects of : ".$buketname;
						foreach ($iterator as $object) {
							echo $object['Key']."\n";
						}
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
		# -- Upload ---
		case 'upload':
			switch ($subDirective) {
				case 'normal':
					// ----------------------------------------------------------------------
					// Normal Uploading
					// Ex. php aisS3client.php upload:normal aisavent-backups image.jpg file.jpg private
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket name
					$filename	=	$argv[3];	// Get New File Name
					$fileloc	=	$aws_default_uploadfrom.'/'.$argv[4];	// Get File name
					$fileacl	=	$argv[5];	// private | public-read | public-read-write | authenticated-read | bucket-owner-read | bucket-owner-full-control
					
					if(!$buketname || !$filename || !$fileloc || !$fileacl){
						echo "Dude! your commands don't seems like they are ok!";
						die();
					}
					
					try {
						$result = $s3Client->putObject(array(
							'ACL'		 => $fileacl,
							'Bucket'     => $buketname,
							'Key'        => $filename,
							'SourceFile' => $fileloc,
							'Metadata'   => array(
								'Agent' => 'aisS3Client'
							)
						));

						// We can poll the object until it is accessible
						$s3Client->waitUntilObjectExists(array(
							'Bucket' => $buketname,
							'Key'    => $filename
						));
						echo "File Uploaded : ".$filename;
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
				case 'big':
					// ----------------------------------------------------------------------
					// Big File Uploading
					// Ex. php aisS3client.php upload:big aisavent-backups 5MB_file.txt 5MB.txt private
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket name
					$filename	=	$argv[3];	// Get New File Name
					$fileloc	=	$aws_default_uploadfrom.'/'.$argv[4];	// Get File name
					$fileacl	=	$argv[5];	// private | public-read | public-read-write | authenticated-read | bucket-owner-read | bucket-owner-full-control
					
					if(!$buketname || !$filename || !$fileloc || !$fileacl){
						echo "Dude! your commands don't seems like they are ok!";
						die();
					}
					try {
						$uploader = UploadBuilder::newInstance()
							->setClient($s3Client)
							->setSource($fileloc)
							->setBucket($buketname)
							->setKey($filename)
							->setConcurrency(3)
							->setOption('ACL', $fileacl)
							->setOption('Metadata', array('Agent' => 'aisS3Client'))
							->setOption('CacheControl', 'max-age=3600')
							->build();
						// Perform the upload. Abort the upload if something goes wrong
						try {
							$uploader->upload();
							echo "File Uploaded : ".$fileloc;
						} catch (MultipartUploadException $e) {
							$uploader->abort();
							echo "File Did not Uploaded : ".$fileloc;
						}
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
					case 'folder':
					// ----------------------------------------------------------------------
					// Folder Uploading
					// Ex. php aisS3client.php upload:folder aisavent-backups testfolder
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket name
					$fileloc	=	$aws_default_uploadfrom.'/'.$argv[3];	// Get Folder Location
					$folde2make	=	$argv[3];	// Get folder name
					
					if(!$buketname || !$fileloc){
						echo "Dude! your commands don't seems like they are ok!";
						die();
					}
					
					try {
						$result = $s3Client->uploadDirectory($fileloc, $buketname.'/'.$folde2make);
						echo "Folder Uploaded with contents: ".$fileloc;
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
					
			}
		break;
		# -- Download ---
		case 'download':
			switch ($subDirective) {
				case 'data':
					// ----------------------------------------------------------------------
					// Download an Objects Data
					// Ex. php aisS3client.php download:data aisavent-backups in_the_cloud.jpg > data.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					try {
						$result = $s3Client->getObject(array(
							'Bucket' => $buketname,
							'Key'    => $objectname
						));
						// Read the body off of the underlying stream in chunks
						while ($data = $result['Body']->read(1024)) {
							//echo $data;
							//echo '<code>'.$data.'</code><br><br>';
						}
						echo $result['Body'];
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
				case 'file':
					// ----------------------------------------------------------------------
					// Download an Objects as file
					// Ex. php aisS3client.php download:file aisavent-backups in_the_cloud.jpg files cool_chick.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					$saveloc	=	$argv[4];	// Get File saving location
					$savename	=	$argv[5];	// File name to save as
					try {
						$result = $s3Client->getObject(array(
							'Bucket' => $buketname,
							'Key'    => $objectname,
							'SaveAs' => $saveloc.'/'.$savename
						));

						// Contains an EntityBody that wraps a file resource of /tmp/data.txt
						echo "File saved at : ".$result['Body']->getUri() . "";
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
		# -- Download ---
		case 'url':
			switch ($subDirective) {
				case 'normal':
					// ----------------------------------------------------------------------
					// Genarate an objects URL (Will only work at public files)
					// Ex. php aisS3client.php url:normal aisavent-backups in_the_cloud.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					try {
						$plainUrl = $s3Client->getObjectUrl($buketname, $objectname);
						echo $plainUrl;
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
				case 'makesimple':
					// ----------------------------------------------------------------------
					// Make an Object Publick & Genarate its URL
					// Ex. php aisS3client.php url:makesimple aisavent-backups in_the_cloud.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					try {
						$makepubl = $s3Client->putObjectAcl(array(
										'ACL' => 'public-read',
										'Bucket' => $buketname,
										'Key' => $objectname,
									));
						$plainUrl = $s3Client->getObjectUrl($buketname, $objectname);
						echo $plainUrl;
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
				case 'limited':
					// ----------------------------------------------------------------------
					// Genarate an objects URL (Will work on any kind og objects)
					// Ex. php aisS3client.php url:limited aisavent-backups in_the_cloud.jpg "30 Seconds"
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					$urltime	=	$argv[4];	// Get Time Limitation
					try {
						$plainUrl = $s3Client->getObjectUrl($buketname, $objectname, $urltime);
						echo $plainUrl;
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
		# -- ACL Control ---
		case 'objectacl':
			switch ($subDirective) {
				case 'public':
					// ----------------------------------------------------------------------
					// Genarate an objects URL (Will only work at public files)
					// Ex. php aisS3client.php objectacl:public aisavent-backups in_the_cloud.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					try {
						$makepubl = $s3Client->putObjectAcl(array(
										'ACL' => 'public-read',
										'Bucket' => $buketname,
										'Key' => $objectname,
									));
						echo "ACL to Public : ".$objectname;
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
					case 'private':
					// ----------------------------------------------------------------------
					// Genarate an objects URL (Will only work at public files)
					// Ex. php aisS3client.php objectacl:private aisavent-backups in_the_cloud.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get File/Object name
					try {
						$makepubl = $s3Client->putObjectAcl(array(
										'ACL' => 'private',
										'Bucket' => $buketname,
										'Key' => $objectname,
									));
						echo "ACL to Private : ".$objectname;
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
		# -- Check Object/Bucket ---
		case 'chk':
			switch ($subDirective) {
				case 'bucketname':
					// ----------------------------------------------------------------------
					// Chk if a Bucket Exists
					// Ex. php aisS3client.php chk:bucketname aisavent-backups
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					try {
						$chkbool = $s3Client->doesBucketExist($buketname);
						if($chkbool){
							echo "Bucket Exists : ".$buketname;
						}else{
							echo "Bucket Do not Exists : ".$buketname;
						}
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
				case 'object':
					// ----------------------------------------------------------------------
					// Chk if an Object Exists
					// Ex. php aisS3client.php chk:object aisavent-backups in_the_cloud.jpg
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get Object Name
					try {
						$chkbool = $s3Client->doesObjectExist($buketname, $objectname);
						if($chkbool){
							echo "Object Exists : ".$objectname;
						}else{
							echo "Object Do not Exists : ".$objectname;
						}
						
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
		# -- Delete Object/Bucket ---
		case 'remove':
			switch ($subDirective) {
				case 'object':
					// ----------------------------------------------------------------------
					// Remove an Object (Version not included)
					// Ex. php aisS3client.php remove:object aisavent-backups in_the_cloud.jpg
					// Ex. For Folder :  php aisS3client.php remove:object aisavent-backups test-folder/
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					$objectname	=	$argv[3];	// Get Object Name
					try {
						$deletobj = $s3Client->deleteObject(array(
										'Bucket' => $buketname,
										'Key' => $objectname,
									));

						echo "Object Deleted : ".$objectname;
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
				case 'bucket':
					// ----------------------------------------------------------------------
					// Remove a Bucket (All Objects will be deleted in the bucket as well!)
					// Ex. php aisS3client.php remove:bucket paglatest
					// ----------------------------------------------------------------------
					$buketname	=	$argv[2];	// Get Bucket Name
					try {
						$clear = new ClearBucket($s3Client, $buketname);
						$clear->clear();
						// Delete the bucket
						$s3Client->deleteBucket(array('Bucket' => $buketname));
						// Wait until the bucket is not accessible
						$s3Client->waitUntilBucketNotExists(array('Bucket' => $buketname));
						echo "Bucket Cleand & Deleted : ". $buketname;
					} catch (\Aws\S3\Exception\S3Exception $e) {
						echo $e->getMessage();
					}
					break;
					// ----------------------------------------------------------------------
			}
		break;
	}
	

