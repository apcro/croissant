<?php
namespace Croissant;

User::Logout();
header('Location: /');
die();