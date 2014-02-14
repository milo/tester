<?php

file_put_contents('php://stderr', "ERR1\n");
echo "OUT1\n";
file_put_contents('php://stderr', "ERR2\n");
echo "OUT2\n";
file_put_contents('php://stderr', "ERR3\n");
