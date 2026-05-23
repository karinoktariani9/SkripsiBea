<?php
$sup = new PDO('pgsql:host=aws-1-ap-northeast-2.pooler.supabase.com;port=6543;dbname=postgres','postgres.csegvqogdpjlbswpgxqz','SkripsiBea_123');
$loc = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=skripsi_bea','postgres','ayam1234');
$loc->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$rows=$sup->query('SELECT id,nama_beasiswa,benua,negara,jenjang,deskripsi,deadline,kategori,jurusan,benefit,persyaratan,sumber,url,url_asli FROM scholarships ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo count($rows).' rows fetched'.PHP_EOL;
$loc->beginTransaction();
$s=$loc->prepare('INSERT INTO scholarships(id,nama_beasiswa,benua,negara,jenjang,deskripsi,deadline,kategori,jurusan,benefit,persyaratan,sumber,url,url_asli) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
foreach($rows as $r){$s->execute(array_values($r));}
$loc->commit();
echo 'Done!'.PHP_EOL;