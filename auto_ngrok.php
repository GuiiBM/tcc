<?php
if (empty(shell_exec('pgrep -f ngrok'))) {
    shell_exec('pkill -f ngrok 2>/dev/null; sleep 1');
    
    $configFile = '/tmp/ngrok.yml';
    $config = "version: \"3\"\nagent:\n    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj\ntunnels:\n    tcc:\n        proto: http\n        addr: 80\n        domain: charlyn-unpaying-zara.ngrok-free.dev\n";
    
    file_put_contents($configFile, $config);
    shell_exec("nohup ngrok start tcc --config $configFile > /dev/null 2>&1 &");
}
?>