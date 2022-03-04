<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
$orderid = '47120';
?>
<!doctype html>
<html>
<head>
    <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>
</head>
<body>
<form id="loginform" method="post">
    <div>
        <input hidden name="orderid" id="orderid" value="<?=$orderid?>" />
        <input type="submit" name="order-print-btn" id="order-print-btn" value="Order Print" />
    </div>
</form>
<script type="text/javascript">
    $(document).ready(function() {
        $('#loginform').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: '/ajax/print-order-ajax.php?doc=print-order&ORDER_ID=<?=$orderid?>&SHOW_ALL=Y',
                data: $(this).serialize(),
                success: function(response){
                    let backup = document.body.innerHTML;
                    document.body.innerHTML = response;
                    window.print();
                    document.body.innerHTML = backup;
                }
            });
        });
    });
</script>
</body>
</html>