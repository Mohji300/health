<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo isset($title) ? $title : 'App'; ?></title>

    <!-- Bootstrap CSS (CDN) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- DataTables CSS (CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico'); ?>">


</head>
<body>

<style>

</style>

<div id="wrapper">
    <?php if (empty($no_sidebar)): ?>
        <?php $this->load->view('templates/sidebar'); ?>
    <?php endif; ?>
    <div id="page-content-wrapper">
        <div class="container-fluid">