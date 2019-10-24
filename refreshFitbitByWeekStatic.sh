echo "Starting script at:" `date` >> /home/esmmprev/batch_fitbit_log.txt
mysql -u dbreg_mpp -p"vHD0Jlf.sW)r" -D dbreg_mpp -h 172.16.221.227 < /home/esmmprev/www/mpp/refreshFitbitByWeekStatic.sql
echo "Ending script at:" `date` >> /home/esmmprev/batch_fitbit_log.txt
echo >> /home/esmmprev/batch_fitbit_log.txt
