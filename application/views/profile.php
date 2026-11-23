<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo html_escape($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
</head>
<body class="bg-light">
<div class="d-flex" id="wrapper">
    <?php $this->load->view('templates/sidebar'); ?>
    <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4">
            <div class="card bg-gradient-primary text-white mb-4">
                <div class="card-body">
                    <h1 class="h2 font-weight-bold mb-2">My Profile</h1>
                    <p class="mb-0 opacity-8">View your account and school information</p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-warning"><?php echo html_escape($error); ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 col-lg-3">Account Name</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->name); ?></dd>

                        <dt class="col-sm-4 col-lg-3">Email</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->email); ?></dd>

                        <dt class="col-sm-4 col-lg-3">Role</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape(ucfirst(str_replace('_', ' ', $user->role))); ?></dd>

                        <dt class="col-sm-4 col-lg-3">School / Office ID</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->school_id); ?></dd>

                        <dt class="col-sm-4 col-lg-3">Address</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->school_address); ?></dd>

                        <dt class="col-sm-4 col-lg-3">School District</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->school_district); ?></dd>

                        <dt class="col-sm-4 col-lg-3">Level / Type</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->school_level); ?></dd>

                        <dt class="col-sm-4 col-lg-3">Head / Officer Name</dt>
                        <dd class="col-sm-8 col-lg-9"><?php echo html_escape($user->school_head_name); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
