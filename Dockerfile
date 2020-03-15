FROM gilbitron/php5.6-mysql

RUN apt-get update
RUN apt-get -y install php5.6-xml --allow-unauthenticated

RUN mkdir /opt/tmserver

WORKDIR /opt/tmserver

COPY server /opt/tmserver
COPY xaseco /opt/xaseco
COPY rcp4 /var/www/html

RUN chmod +x /opt/tmserver/RunTrackmaniaServer.sh
RUN chmod +x /opt/tmserver/TrackmaniaServer

EXPOSE 80
EXPOSE 5000
EXPOSE 2350
EXPOSE 3450

CMD ["/opt/tmserver/RunTrackmaniaServer.sh"]
