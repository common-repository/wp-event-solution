
<?php include Wpeventin::templates_dir() . 'emails/header.php'; ?>
    
<!-- BEGIN BODY -->
<?php include Wpeventin::templates_dir() . 'emails/' . $template . '.php'; ?>
<!-- END BODY -->

<?php include Wpeventin::templates_dir() . 'emails/footer.php'; ?>