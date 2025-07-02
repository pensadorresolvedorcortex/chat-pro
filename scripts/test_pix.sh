#!/bin/bash
set -e
php scripts/test_pix.php
zbarimg /tmp/sample_pix.png
