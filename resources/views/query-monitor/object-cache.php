<?= $this->before_non_tabular_output(); ?>
    <section>
        <h3>Hit Ratio</h3>
        <p class="qm-ltr"><code><?= esc_html($data['ratio']); ?>%</code></p>
    </section>

    <section>
        <h3>Hits</h3>
        <p class="qm-ltr"><code><?= esc_html($data['hits']); ?></code></p>
    </section>

    <section>
        <h3>Misses</h3>
        <p class="qm-ltr"><code><?= esc_html($data['misses']); ?></code></p>
    </section>

    <?php if (!empty($data['requests'])): ?>
        <section>
            <h3>Requests to <?= esc_html($data['type']); ?></h3>
            <p class="qm-ltr"><code><?= esc_html($data['requests']); ?></code></p>
        </section>
    <?php endif; ?>

    <?php if (!empty($data['request_time'])): ?>
        <section>
            <h3>Total time (ms)</h3>
            <p class="qm-ltr"><code><?= esc_html($data['request_time']); ?></code></p>
        </section>
    <?php endif; ?>
<?= $this->after_non_tabular_output(); ?>
