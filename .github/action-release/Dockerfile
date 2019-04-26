FROM debian:stable-slim

LABEL "com.github.actions.name"="Release to Stable"
LABEL "com.github.actions.description"="Push a production-ready build to the stable branch"
LABEL "com.github.actions.icon"="send"
LABEL "com.github.actions.color"="blue"

LABEL maintainer="Helen Hou-Sandí <helen.y.hou@gmail.com>"
LABEL version="1.0.0"
LABEL repository="http://github.com/10up/classifai/.github/action-release"

RUN apt-get update \
	&& apt-get install -y rsync git \
	&& apt-get clean -y \
	&& rm -rf /var/lib/apt/lists/* \
	&& git config --global user.email "10upbot+github@10up.com" \
	&& git config --global user.name "10upbot on GitHub"

COPY entrypoint.sh /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]