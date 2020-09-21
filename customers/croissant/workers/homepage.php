<?php
namespace Croissant;

// Debug::PointTime('Add Javascript');
// Custom Javascript elements
// Core::AddJavascript('homepage.js');

Debug::PointTime('Add CSS');
// Custom CSS elements
Core::AddCSS('homepage.css');

Debug::PointTime('Set Template & Title');
// page-specific settings
Core::Template('homepage/homepage.tpl');
Core::PageTitle('Croissant Framework');
