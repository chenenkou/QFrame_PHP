<?php

/**
 * 测试Cli类
 * Class TestCommand
 */
class TestCommand extends Command
{
    public function execute($name = 'TestCommand') {
        pL($name);
    }
}