SELECT setval(pg_get_serial_sequence('"user"', 'id'), (SELECT COALESCE(MAX(id), 1) FROM "user"));
SELECT setval(pg_get_serial_sequence('media', 'id'), (SELECT COALESCE(MAX(id), 1) FROM media));
SELECT setval(pg_get_serial_sequence('album', 'id'), (SELECT COALESCE(MAX(id), 1) FROM album));
