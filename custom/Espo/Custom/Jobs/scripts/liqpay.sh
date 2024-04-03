#!/bin/bash
PUBLIC_KEY='sandbox_i73239460883'
PRIVATE_KEY='sandbox_cdjz5QKGN1XG0ynqnOWDfBxqWHaImxSKVtFIrcvv'
JSON="{ 
\"version\" : 3,
\"public_key\" : \"${PUBLIC_KEY}\", 
\"action\" : \"pay\", 
\"amount\" : 1, 
\"currency\" : \"USD\",
\"description\" : \"description text\",
\"order_id\" : \"order_id_1\"
}"
# DATA is base64_encode result from JSON string
DATA=$(echo -n ${JSON} | base64)
# SIGNATURE is base64 encode result from sha1 binary hash from concatenate string ${PRIVATE_KEY}${DATA}${PRIVATE_KEY}
SIGNATURE=$(echo -n "${PRIVATE_KEY}${DATA}${PRIVATE_KEY}" | openssl dgst -binary -sha1 | base64)
echo "data: ${DATA}"
echo "signature: ${SIGNATURE}"

# DATA in this example
# eyAidmVyc2lvbiIgOiAzLCAicHVibGljX2tleSIgOiAieW91cl9wdWJsaWNfa2V5IiwgImFjdGlv
# biIgOiAicGF5IiwgImFtb3VudCIgOiAxLCAiY3VycmVuY3kiIDogIlVTRCIsICJkZXNjcmlwdGlv
# biIgOiAiZGVzY3JpcHRpb24gdGV4dCIsICJvcmRlcl9pZCIgOiAib3JkZXJfaWRfMSIgfQ==

# SIGNATURE in this example
# QvJD5u9Fg55PCx/Hdz6lzWtYwcI=