<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command {
    // 类中configure()函数命令行中使用list命令列出所有任务的时候回显出的摘要提示
    // php think list
    protected function configure() {
        // 设置命令名称 以及 命令说明
        $this->setName('test')->setDescription('Command Test');
    }

    // execute()函数是要执行的命令，这里可以直接写需要完成的任务或者调用类中任务方法。
    // php think test
    protected function execute(Input $input, Output $output) {
        // 脚本逻辑在此处理
        $data = db('ls_tag')->find(1);
        print_r($data);
        $output->writeln("TestCommand:");
    }
}