<?php
namespace SPSync\v2;
use FileUploader;
use JobQueueChunk;

class DeleteFileJobQueueChunk extends JobQueueChunk{
    
    public static $name = 'deleteFile';
    
    public function run($params) {
        if(!empty($params['files']) && is_array($params['files'])) {
            foreach($params['files'] as $file){
                if(!empty($file['filePath'])) {
                    FileUploader::deleteFile($file['filePath']);
                }
                if(!empty($file['thumbPath'])) {
                    FileUploader::deleteFile($file['thumbPath']);
                }
                echo "Deleted " . $file['filePath'] . "\n";
            }
        }
    }
    
}