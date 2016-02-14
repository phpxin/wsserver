all: wsserver

CC = gcc
CFLAGS = -g 
#-Wall -ansi -D_REENTRANT

SSLDIR = /usr/lib64/openssl/

SRCF = ./src/
OUTPUTF = ./output/
BINF = ./bin/

INCLUDE = -I./h/
CLIB = -L${SSLDIR}engines/ -lcapi -lpthread

wsserver: ${OUTPUTF}main.o ${OUTPUTF}base64.o ${OUTPUTF}cencode.o ${OUTPUTF}cdecode.o ${OUTPUTF}HString.o
	${CC} ${CFLAGS} -o ${BINF}wsserver ${OUTPUTF}main.o ${OUTPUTF}base64.o ${OUTPUTF}cencode.o ${OUTPUTF}cdecode.o ${OUTPUTF}HString.o ${CLIB}

#create all c to o
${OUTPUTF}%.o: ${SRCF}%.c
	${CC} ${CFLAGS} ${INCLUDE} -c $< -o $@

clean:
	rm -rf ${OUTPUTF}*.o ${BINF}wsserver
