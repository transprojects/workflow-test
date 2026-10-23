<?php if (isWordFile($app_res) && isCustomerSpecial($customer['cust_id'])): ?>

    <iframe class="tool-iframe"
            src="http://<?= $ceditor_ip ?>/Transforma/products/word-to-pdf-xml_workflow.php?wid=<?= $aid ?>">
    </iframe>

<?php endif; ?>
