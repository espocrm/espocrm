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


cp -r $PROJECT"/client/" $BUILD"/client/custom";
cp -r $PROJECT"/custom/Espo/" $BUILD"/custom/Espo/Custom";
cp $PROJECT"/data" $BUILD"/data/config.php";


echo "Done!";
