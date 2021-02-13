echo >> /home/esmmwl/aso/aso.log
echo >> /home/esmmwl/aso/aso.log
echo >> /home/esmmwl/aso/aso.log

date >> /home/esmmwl/aso/aso.log
ls -ltr /home/esmmwl/aso/upload >> /home/esmmwl/aso/aso.log 

echo >> /home/esmmwl/aso/aso.log

scp -v /home/esmmwl/aso/upload/* EatSmart_TSFTP@mfts.bcbsnc.com:/ 2>>/home/esmmwl/aso/aso.log
mv /home/esmmwl/aso/upload/* /home/esmmwl/aso/archive

