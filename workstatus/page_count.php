<div class="row mt-3">

    <div class="col-lg-3">
        <label class="form-label">
            Page Count <span class="text-danger">*</span>
        </label>

        <input type="number"
               class="form-control"
               name="page_count"
               id="page_count"
               min="0"
               value="<?= $app_res['page_count'] ?: 0 ?>"
               required>
    </div>

</div>
