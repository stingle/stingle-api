<?php

use SPSync\v2\SPSyncManager;

ob_end_flush();
Reg::get('ao')->disableOutput();

$sqlMain = MySqlDbManager::getQueryObject();
$qb = new QueryBuilder();

$qb->select(new Field('id', 'u'))
    ->from(Tbl::get('TBL_USERS', 'UserManager'), 'u')
    ->andWhere($qb->expr()->equal(new Field('enabled', 'u'), 1));

$sqlMain->exec($qb->getSQL());

while (($row = $sqlMain->fetchRecord()) != false) {
    $user = Reg::get('userMgr')->getUserById($row['id']);
    $size = Reg::get('spsync')->getTotalUsedSpaceForUser($user);
    $mbSize = SPSyncManager::bytesToMb($size);
    
    $originalSpaceUsed = $user->props->spaceUsed;
    $user->props->spaceUsed = $mbSize;
    Reg::get('userMgr')->updateUser($user);
    if($originalSpaceUsed != $user->props->spaceUsed) {
        echo "Fixed user: " . $row['id'] . " - " . $mbSize . " Mb\n";
    }
}

echo "\n\nFinished\n\n";
exit;