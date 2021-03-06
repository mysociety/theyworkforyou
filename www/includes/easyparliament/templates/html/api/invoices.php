<section class="account-form">
    <h2>Invoices</h2>

    <p>Below you can download invoices from the past two years.</p>

    <table class="striped">
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
    <?php foreach ($subscription->invoices() as $invoice) { ?>
        <tr align="center">
            <td><?= $invoice->number ?></td>
            <td><?= date('d/m/Y', $invoice->status_transitions->finalized_at) ?></td>
            <td>£<?= number_format($invoice->amount_due / 100, 2); ?></td>
            <td><?= ucfirst($invoice->status) ?><td>
            <td><?php if ($invoice->status == 'paid') { ?>
                <a href="<?= $invoice->invoice_pdf ?>">Download PDF</a>
            <?php } ?>
            </td>
        </tr>
    <?php } ?>
    </table>

</section>
