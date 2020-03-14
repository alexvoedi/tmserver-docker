FROM debian:stretch-slim

RUN mkdir /opt/tmserver

WORKDIR /opt/tmserver

COPY server /opt/tmserver
COPY RunTrackmaniaServer.sh /opt/tmserver/

EXPOSE 5000/tcp
EXPOSE 2350/tcp
EXPOSE 2350/udp
EXPOSE 3450/tcp

CMD ["/opt/tmserver/RunTrackmaniaServer.sh"]
