PYTHON ?= python3
ARGS ?=

.PHONY: install-dev validate validate-openapi validate-sync readiness-snapshot

install-dev:
	$(PYTHON) -m pip install -r requirements-dev.txt

validate-openapi:
	$(PYTHON) scripts/validate_openapi.py $(ARGS)

validate-sync:
	$(PYTHON) scripts/validate_sync.py $(ARGS)

validate: validate-openapi validate-sync

readiness-snapshot:
	$(PYTHON) scripts/ops/readiness_snapshot.py $(ARGS)
