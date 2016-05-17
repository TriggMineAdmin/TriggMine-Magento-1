<h1>Triggmine integration for Magento ver. 1.9.x</h1>

//Go to our official Magento 2.0 public repository - LINK HERE.

Download it as a ZIP.


Unzip the file.


Drop/move all of the content inside the unzipped folder to directory 'Magento_Root/' on FTP.

Run the command 'composer update' in 'Magento_Root/lib/Triggmine' directory . This will get needed dependencies via Composer.

Go to System > Cache management  and  flush Magento cache and cache storage.

Go to System > Configuration(General tab) >Triggmine and enter the API credentials.
