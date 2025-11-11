#!/bin/bash

# Script CRON pour traiter les jobs de queue MongoDB
# À exécuter toutes les minutes en production

# Traiter tous les jobs en attente dans la queue notifications
php /opt/render/project/src/artisan queue:process-mongodb --queue=notifications --once

exit 0
