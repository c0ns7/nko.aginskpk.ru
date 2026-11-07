<?php
logout_user();
header('Location: ' . url('admin/login'));
exit;
