# config/judge.php
<?php

return [
    'time_limit_ms_default' => 2000,
    'memory_limit_mb_default' => 256,

    'languages' => [
        'cpp' => [
            'src'     => 'main.cpp',
            'compile' => ['/usr/bin/g++','-O2','-std=gnu++17','/work/main.cpp','-o','/work/Main'],
            'run'     => ['/work/Main'],
        ],
        'python' => [
            'src'     => 'main.py',
            'compile' => null,
            'run'     => ['/usr/bin/python3','/work/main.py'],
        ],
        'java' => [
            'src'     => 'Main.java',
            'compile' => ['/usr/bin/javac','/work/Main.java'],
            'run'     => ['/usr/bin/java','-Xss256m','-Xmx256m','-Duser.language=en','-Dfile.encoding=UTF-8','-classpath','/work','Main'],
        ],
    ],
];
