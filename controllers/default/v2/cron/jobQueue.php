<?php

use SPSync\v2\DeleteFileJobQueueChunk;

ob_end_flush();

Reg::get('jobQueue')->addChunk(new DeleteFileJobQueueChunk());
$event = array('jobQueue' => Reg::get('jobQueue'));
HookManager::callHook('RegisterJobQueueChunks', $event);

Reg::get('jobQueue')->runQueue();

exit;
