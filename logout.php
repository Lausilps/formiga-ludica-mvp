<?php

require_once 'helpers/sessaoHelper.php';
iniciarSessaoPersistente();
session_destroy();

header("Location: login.php");
exit;