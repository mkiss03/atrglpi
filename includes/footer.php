            </main>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-0 text-muted">
                                &copy; <?= date('Y') ?> ÁTR Beragadt Betegek Nyilvántartó Rendszer
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-0 text-muted">
                                <small>v1.0.0</small>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            // Initialize Select2 for osztaly dropdown (rögzítés oldal)
            if ($('#osztaly').length) {
                $('#osztaly').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Kezdj el gépelni osztálykódra vagy névre...',
                    allowClear: true,
                    width: '100%',
                    language: {
                        noResults: function() {
                            return 'Nincs találat';
                        },
                        searching: function() {
                            return 'Keresés...';
                        }
                    }
                });
            }

            // Initialize Select2 for search dropdown (lista oldal)
            if ($('#search').length) {
                $('#search').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Kezdj el gépelni osztálykódra vagy névre...',
                    allowClear: true,
                    width: '100%',
                    language: {
                        noResults: function() {
                            return 'Nincs találat';
                        },
                        searching: function() {
                            return 'Keresés...';
                        }
                    }
                });
            }

            // Form reset button
            $('#resetForm').on('click', function(e) {
                e.preventDefault();
                $('#atrForm')[0].reset();
                if ($('#osztaly').length) {
                    $('#osztaly').val(null).trigger('change');
                }
            });

            // Confirm delete
            $('.btn-delete').on('click', function(e) {
                if (!confirm('Biztosan törölni szeretnéd ezt a rekordot?')) {
                    e.preventDefault();
                    return false;
                }
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>
