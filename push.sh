#!/bin/bash
SERVER="www-data:www-data";
CLIENT="hw:hw";

PROJECT="/var/www/html";    
BUILD=$PROJECT"/build/dsfreedom";

FRONTEND="/client";
BACKEND="/cutom/Espo";

#project custom code path
FRONTEND_SRC=$PROJECT$FRONTEND"/custom";
BACKEND_SRC=$PROJECT$BACKEND"/Custom";
DATA_SRC=$PROJECT"/data";


cp -r $PROJECT"/client/custom" $BUILD"/client";
cp -r $PROJECT"/custom/Espo/Custom" $BUILD"/custom/Espo";
cp $PROJECT"/data/config.php" $BUILD"/data";


echo "Done!";
