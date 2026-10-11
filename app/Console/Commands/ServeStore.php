<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand;

class ServeStore extends ServeCommand
{
    protected function serverCommand()
    {
        $command = parent::serverCommand();
        array_splice($command, 1, 0, ['-d', 'upload_max_filesize=8M', '-d', 'post_max_size=10M']);

        return $command;
    }
}
