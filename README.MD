##Triggmine integration for Magento ver. 1.9.x

#####Installation steps:
1. Download it as a ZIP [from here](https://github.com/TriggMineAdmin/TriggmineMagento1/archive/master.zip).
2. Unzip the file.
3. Drop/move all of the content inside the unzipped folder to directory 'Magento_Root/' on FTP.
4. Run the command **composer update** in '[Magento_Root]/lib/Triggmine' directory. This will get needed dependencies via Composer.
5. Go to System > Cache management  and  flush Magento cache and cache storage.
6. Go to System > Configuration(General tab) >Triggmine and enter the API credentials.
