<?php

error_reporting(-1);

require_once('bootstrap.php');

$app = new \Espo\Core\Application();

echo $app->getContainer()->get('fileManager')->setContent('id,name,website,emailAddress,phone,fax,type,industry,sicCode,billingAddressStreet,billingAddressCity,billingAddressState,billingAddressCountry,billingAddressPostalCode,shippingAddressStreet,shippingAddressCity,shippingAddressState,shippingAddressCountry,shippingAddressPostalCode,description,createdAt,modifiedAt,teamsIds,teamsNames,deleted,assignedUserName,assignedUserId,modifiedByName,modifiedById,createdByName,createdById,isFollowed
1,Brom,http://brom.com,info@brom.com,+43242673387,,Investor,Education,,,Chernivtsi,,Ukraine,58000,,,,,,,,"2014-01-10 15:45:15",,,,Admin,1,Admin,1,,,
2,"Metan Inc",,,+3008765567,,,,,,,,,,,,,,,,"2013-12-20 11:44:42","2013-12-27 14:45:17",,,,Admin,1,Admin,1,Admin,1,
52babbbc92000,"Magala LTD",http://magalaltd.com,info@magalaltd.com,+380954564054,+380954564057,Investor,Finance,54005,,Magala,,Ukraine,58000,,Magala,,Ukraine,58000,"Invested $200,000","2013-12-25 11:04:28","2014-01-09 09:48:00",,,,"Will Manager",52bc41e60ccba,Admin,1,Admin,1,
52babe34b1d3a,Letrium,http://letrium.com,,,,,IT,,"Rivnenska 5a",Chernivtsi,,Ukraine,58013,,,,,,,"2013-12-25 11:15:00","2014-01-02 10:24:22",,,,Admin,1,Admin,1,Admin,1,
', 'data/upload/4545454544');


