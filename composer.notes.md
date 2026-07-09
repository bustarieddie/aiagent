# Packages to add (on top of a fresh Laravel 11/12 app)

composer require livewire/livewire
composer require smalot/pdfparser        # PDF text extraction (Gnosis reports)
composer require barryvdh/laravel-dompdf # HTML -> PDF rendering

# Register the private storage disk (config/filesystems.php) — NOT public:
# 'local' disk already exists and is non-public. Keep lab PDFs there.
