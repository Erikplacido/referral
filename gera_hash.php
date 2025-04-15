<?php
$senhaPura = "senha"; // Substitua pela senha que você quer
$hash = password_hash($senhaPura, PASSWORD_DEFAULT);
echo $hash;