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


cp -r $BUILD"/client/custom" $PROJECT"/client/";
cp -r $BUILD"/custom/Espo/Custom" $PROJECT"/custom/Espo/";

echo "Done!";