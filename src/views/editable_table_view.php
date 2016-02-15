<div class="table-responsive">
    <div class="form-group form-inline">
        <label>Width:</label>
        <input type="number" class="form-control" step="1" min="0" id="table-width">
        <label>Height:</label>
        <input type="number" class="form-control" step="1" min="0" id="table-height">
        <label>Append:</label>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" id="append-top">Top</button>
            <button type="button" class="btn btn-primary" id="append-bottom">Bottom</button>
            <button type="button" class="btn btn-primary" id="append-left">Left</button>
            <button type="button" class="btn btn-primary" id="append-right">Right</button>
        </div>
    </div>
    <table class="table table-bordered table-fixed" id="editable-table" session-token=<?= $user_session_token ?>>
        <tbody>
            <?php foreach ($editable_table as $row): ?>
            <tr>
                <?php foreach ($row as $value): ?>

                <td contenteditable><?= htmlspecialchars($value) ?></td>

                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="btn-group">
        <button type="button" class="btn btn-primary" id="restore">Restore</button>
        <button type="button" class="btn btn-primary" id="interactive-resize">Interactive Crop</button>
        <button type="button" class="btn btn-primary" id="save">Save</button>
    </div>
</div>
