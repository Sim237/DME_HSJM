    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Supprimer les modal-backdrop
            $('.modal-backdrop').remove();
            $('.modal').removeClass('show').hide();
            $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
        });
    </script>
</body>
</html>