<?php
    if (!isset($bodyOnly)) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {font-family: Arial;}

        /* Style the tab */
        .costOfPriceInfoTab {
            overflow: hidden;
            border: 1px solid #ccc;
            background-color: #f1f1f1;
        }

        /* Style the buttons inside the tab */
        .costOfPriceInfoTab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 16px;
            transition: 0.3s;
            font-size: 17px;
        }

        /* Change background color of buttons on hover */
        .costOfPriceInfoTab button:hover {
            background-color: #ddd;
        }

        /* Create an active/current tablink class */
        .costOfPriceInfoTab button.active {
            background-color: #ccc;
        }

        /* Style the tab content */
        .priceinfotab {
            display: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-top: none;
        }
    </style>
</head>
<body>
<?php } ?>

<!--<h2>Tabs</h2>
<p>Click on the buttons inside the tabbed menu:</p>
-->
<div class="costOfPriceInfoTab">
    <?php echo $priceInfoTabs ?>
</div>

<?php echo $priceInfoBlocks ?>

<script>
    function openPriceInfo(evt, methodName) {
        var i, tabcontent, priceinfotablink;
        tabcontent = document.getElementsByClassName("priceinfotab");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        priceinfotablink = document.getElementsByClassName("priceinfotablink");
        for (i = 0; i < priceinfotablink.length; i++) {
            priceinfotablink[i].className = priceinfotablink[i].className.replace(" active", "");
        }
        document.getElementById(methodName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    window.onload = function() {
        var tabcontent = document.getElementsByClassName("priceinfotab");
        var currentID;
        for (var i = 0; i < tabcontent.length; i++) {
            currentID = tabcontent[i].id;
            break;
        }
        if (currentID !== '') {
            openPriceInfo(null, currentID);
        }
    }
</script>

<?php
if (!isset($bodyOnly)) {
?>

</body>
</html>
<?php } ?>
