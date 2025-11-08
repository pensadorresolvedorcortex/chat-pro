# Juntaplay Plugin Source

This repository contains the unbundled source for the Juntaplay WordPress plugin. The build
artifacts that WordPress expects (such as distributable `.zip` packages) must be generated
locally when preparing a release and distributed outside of git. Storing binary archives in
this repository is not supported.

To create a release package locally, run your preferred archive command from the repository
root, for example:

```bash
zip -r juntaplay.zip juntaplay
```

Do not commit the resulting archive—keep the working tree clean or move the file to your
release pipeline storage instead.
