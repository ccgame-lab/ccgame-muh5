mysqldump -u root -pnG7rKWGyzbMX8Wbd --no-tablespaces muh5_ccgame > /root/muh5_ccgame_snapshot.sql
mysql -u root -pnG7rKWGyzbMX8Wbd -e "CREATE DATABASE IF NOT EXISTS ccgame_muh5_stage CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -pnG7rKWGyzbMX8Wbd ccgame_muh5_stage < /root/muh5_ccgame_snapshot.sql
mysql -u root -pnG7rKWGyzbMX8Wbd -e "GRANT ALL PRIVILEGES ON ccgame_muh5_stage.* TO 'root_muh5'@'localhost'; FLUSH PRIVILEGES;"
