FROM debian:stretch-slim

RUN mkdir /opt/tmserver

WORKDIR /opt/tmserver

COPY server /opt/tmserver

RUN chmod +x /opt/tmserver/RunTrackmaniaServer.sh
RUN chmod +x /opt/tmserver/TrackmaniaServer

EXPOSE 5000
EXPOSE 2350
EXPOSE 3450

CMD ["/opt/tmserver/RunTrackmaniaServer.sh"]
