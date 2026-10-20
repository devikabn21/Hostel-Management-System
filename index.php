<?php
require_once __DIR__ . '/includes/bootstrap.php';
redirect(is_admin() ? 'dashboard.php' : 'login.php');