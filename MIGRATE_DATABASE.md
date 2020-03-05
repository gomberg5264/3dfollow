# Prepare SQL dumps

```bash
sudo apt install sqlite3

cd application

# Export sqlite data as SQL dump
rm -f data.sql
sqlite3 var/data.db .schema > schema.sql
sqlite3 var/data.db .dump > dump.sql
grep -vx -f schema.sql dump.sql > data.sql
rm -f schema.sql dump.sql

# Clean dump
sed -i -e 's/INTO "user" /INTO users /g' data.sql
sed -i '/PRAGMA/d' data.sql
sed -i '/sqlite_sequence/d' data.sql
sed -i '/group_user/d' data.sql
sed -i '/groups/d' data.sql
sed -i '/BEGIN TRANSACTION/d' data.sql
sed -i '/COMMIT/d' data.sql

sed -i -e "s/,1,/,'1',/g" data.sql
sed -i -e "s/,1)/,'1')/g" data.sql
sed -i -e "s/,0,/,'0',/g" data.sql
sed -i -e "s/,0)/,'0')/g" data.sql

sed -i -e 's/INTO users /INTO users (id, username, is_admin, password, is_printer) /g' data.sql
sed -i -e 's/INTO "filament" /INTO filament (id, name, weight, price, density, diameter, owner_id) /g' data.sql
sed -i -e 's/INTO "print_item" /INTO print_item (id, name, link, comment, created_at, is_printed, weight, filament_id, quantity, user_id, team_id) /g' data.sql
sed -i -e 's/INTO "team" /INTO team (id, join_token, creator_id) /g' data.sql

# Split SQL files
grep 'INTO "team_user" ' data.sql > data2.sql
sed -i '/INTO "team_user" /d' data.sql
```

# Import dumps

In the dev environment, connect to builder container first : `fab builder`

```bash
bin/console d:d:d --force && bin/console d:d:c && bin/console doctrine:migration:migrate -n

# Import data in PG
PGPASSWORD=07b611df693ed272f8cdbec2b420a792 psql -h localhost -U 3d_follow 3d_follow < data.sql
PGPASSWORD=07b611df693ed272f8cdbec2b420a792 psql -h localhost -U 3d_follow 3d_follow < data2.sql
```
