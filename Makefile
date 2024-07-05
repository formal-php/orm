# This command is intended to be run on your computer
serve-doc:
	docker build -t squidfunk/mkdocs-material .
	docker run --rm -it -p 8000:8000 -v ${PWD}:/docs squidfunk/mkdocs-material

build-doc:
	docker build -t squidfunk/mkdocs-material .
	docker run --rm -it -v ${PWD}:/docs squidfunk/mkdocs-material build
