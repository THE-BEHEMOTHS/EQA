<!DOCTYPE html>
<html>
<head>
	<title>WELCOME TO EQA</title>
</head>
<body>
	<h2 style="font-weight: bold;">Hello, <?= @$username; ?></h2>
	<p style="margin-top: 15px;">First of all, Welcome to EQA.</p>
    <p>You have been added as a(n) <?= @ucwords($role); ?> in the system.</p>
	<p>Please follow the link below to activate your email</p>
	<div style="width: 100%; background: #B0BEC5; color: #ffffff;padding: 10px;"><?= @$url; ?></div>
</body>
</html>