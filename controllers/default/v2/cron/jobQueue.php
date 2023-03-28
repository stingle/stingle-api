<?php

use SPSync\v2\DeleteFileJobQueueChunk;

ob_end_flush();

Reg::get('jobQueue')->addChunk(new DeleteFileJobQueueChunk());

Reg::get('jobQueue')->runQueue();

exit;