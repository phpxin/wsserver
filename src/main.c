//unix
#include <unistd.h>
#include <stdlib.h>
#include <sys/types.h>
#include <fcntl.h>
//memory and io
#include <string.h>
#include <stdio.h>
#include <pthread.h>
//socket
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
//signal
#include <signal.h>
//data structs
#include "HString.h"
//sign
#include <openssl/evp.h>
#include <b64/base64.h>
//mulit
#include <sys/epoll.h>
// error oper
#include <errno.h>

#define IP "192.168.3.17"
#define PORT 10002

#define BS 1024   ///buff size
#define EP_LIMIT 200


#define METHOD_GET 1
#define METHOD_POST 2

#define CLI_LIMIT 100

//#define WWW_ROOT "/home/public/htdocs"

void read_header(int cfd) ;
int read_line(int cfd, void **buf, int *rlen);
void response(int);
void sigfunc_callback(int sig);
void sig_chld_func();
void sig_int_func();
char* keygen(const char *key);
static void *thread_func(void *udata); /* 连接线程 */

typedef struct {
    char accept_key[1024];
} RequestInfo;

RequestInfo req_info = {"\0"} ;

int serv_sock_f ;
pthread_t thread_c;
// about epoll
int client_heap[EP_LIMIT] ; // 游戏人数
int ep_servf = -1;
int ep_clients= -1;
struct epoll_event *event_ok = NULL;
struct epoll_event *ce_ok = NULL;
// about epoll end

//客户端连接符集合
int cli_vertor[CLI_LIMIT] ;
int cli_counter = 0 ;

int main()
{
    printf("welcome ! \n");

    signal(SIGCHLD, sigfunc_callback);
    signal(SIGINT, sigfunc_callback);

    struct sockaddr_in serv_addr;
	serv_sock_f = socket(AF_INET, SOCK_STREAM, 0);

    serv_addr.sin_family = AF_INET;
	serv_addr.sin_port = htons(PORT);
	inet_pton( AF_INET, IP, &serv_addr.sin_addr.s_addr);


    int flag=1 ;

    // SO_REUSEADDR 设置端口重用，否则中断程序重启会提示端口被使用，需要过一段时间才能使用
    int len=sizeof(int);
    if( setsockopt(serv_sock_f, SOL_SOCKET, SO_REUSEADDR, &flag, len) == -1)
    {
       perror("setsockopt");
       exit( -1 );
    }
    //发现设置端口重用有个问题，如果中断了该端口通信（通信正在进行中），重用的端口的新程序会继续接收上次未完成的数据（然后就杯具了%>_<%）

	flag = bind(serv_sock_f, (struct sockaddr *)&serv_addr, sizeof(serv_addr));


    if(flag == -1)
	{
        perror("bind");
		exit( -1 );
	}

	flag = listen(serv_sock_f, 5);
	if(flag == -1)
	{
		perror("listen");
		exit( -1 );
	}

    struct sockaddr_in client_addr;
	socklen_t client_sock_l = 0;
	int client_sock_f = -1;


    //lx@0109 增加epoll代码
    ep_servf = epoll_create(5);
    ep_clients = epoll_create(5);

	struct epoll_event _event;
	_event.data.fd = serv_sock_f;
    _event.events = EPOLLIN ;

	flag = epoll_ctl(ep_servf, EPOLL_CTL_ADD, serv_sock_f, &_event);
    if (flag == -1) {
		//elog(E_ERROR, "epoll_ctl failed error num is %d", errno);
        perror("epoll_ctl");
        exit( -1 );
    }

	event_ok = (struct epoll_event *)calloc( 1, sizeof(struct epoll_event));

    flag = pthread_create(&thread_c, NULL, thread_func, NULL);
	if(flag != 0)
	{
		//elog(E_ERROR, "run child thread failed error num is %d", errno);
        perror("pthread_create");
		exit( -1 );
	}
    //end

    int client_len = 0;
    while(1)
	{
        int isok=epoll_wait(ep_servf, event_ok, 1, -1) ;
        if(isok>0 && event_ok[0].data.fd == serv_sock_f)
        {
            if(client_len >= EP_LIMIT){
                printf("warning");
            }

            client_sock_f = accept(serv_sock_f, (struct sockaddr *)&client_addr, &client_sock_l);
            //for login
            read_header(client_sock_f);
            response(client_sock_f);
            //login done  begin msg ...

            // record to client pool
            client_heap[client_len] = client_sock_f ;
            client_len++ ;

            /* 添加到客户端epoll */
            cli_vertor[cli_counter] = client_sock_f;
            cli_counter++;
			_event.data.fd = client_sock_f;
    		_event.events = EPOLLIN | EPOLLOUT ;
			epoll_ctl(ep_clients, EPOLL_CTL_ADD, client_sock_f, &_event);
        }
    }

    // >>>>>>>>>> test
    /*
    client_sock_f = accept(serv_sock_f, (struct sockaddr *)&client_addr, &client_sock_l);
    //for login
    read_header(client_sock_f);
    response(client_sock_f);
    */
    //login done  begin msg ...
    /*
    while(1){

        void *frameBuff = malloc(2);
        //int recv( _In_ SOCKET s, _Out_ char *buf, _In_ int len, _In_ int flags);
        recv(client_sock_f, frameBuff, 2, 0);

        unsigned char _infos;
        memcpy(&_infos, frameBuff, 1);

        //0 FIN
        //1-3 RSV1-3
        //4-7 opcode

        unsigned char fin = _infos>>7;
        printf("fin is %u \n", fin);

        unsigned char _len, len_flag;
        memcpy(&_len, frameBuff+1, 1);

        //0 MASK
        //1-7 Payload len

        unsigned long len = _len ;
        len_flag = _len;

        unsigned char mask = _len>>7;
        printf("mask is %u\n", mask);

        unsigned char _mask[4];
        if(mask){
            len_flag = len = _len^128 ;
            //recv(client_sock_f, _mask, 4, 0);
        }

        if(len_flag>125){   //暂时不支持大内容数据
            if (len_flag==126) {
                // code
                char xx[2];
                recv(client_sock_f, xx, 2, 0);
                unsigned short _len16 ;
                memcpy(&_len16, xx, 2);
                len = ntohs(_len16);
            }else if(len_flag==127){
                char xx[8];
                recv(client_sock_f, xx, 8, 0);
                unsigned long _len64 ;
                memcpy(&_len64, xx, 8);
                len = ntohl(_len64);
            }else{
                printf("content too long \n");
                exit(-1);
            }
        }

        printf("len is %u \n", len);

        if(mask){
            recv(client_sock_f, _mask, 4, 0);
        }

        char *content = (char *) malloc(len);
        recv(client_sock_f, content, len, 0);

        if(mask){
            // 解码
            int i;
            for(i=0; i<len; i++){
                content[i] = content[i] ^ _mask[i%4] ;
            }
        }

        printf("recv: %s\n", content);

        // response
        size_t _send_len = 32*4 + len , send_len = 0  ; // 待发送的数据长度
        void *sendBuff = malloc(_send_len);

        unsigned char response_1b = 0 ;
        response_1b = response_1b | (1<<7) ;
        response_1b = response_1b | 1 ;
        unsigned char response_2b = len_flag;

        memcpy(sendBuff, &response_1b, 1);
        memcpy(sendBuff+1, &response_2b, 1);
        send_len+=2;

        if (len_flag==126) {

            unsigned short _tmp = htons(len);
            memcpy(sendBuff+2, &_tmp, 2);
            send_len += 2;
        }else if(len_flag==127){

            unsigned long _tmp = htonl(len);
            memcpy(sendBuff+2, &_tmp, 8);
            send_len += 8;
        }

        memcpy(sendBuff+send_len, content, len) ;
        send_len += len;
        send(client_sock_f, sendBuff, send_len, 0);

        //清理内存 ╭(╯^╰)╮
        free(sendBuff);
        sendBuff = NULL;
        free(content);
        content = NULL;
        free(frameBuff);
        frameBuff = NULL;
    }
    */

    //disconnection
    //close(client_sock_f) ;
    //close(serv_sock_f);
    // >>>>>>>>>> test end
    /*

    while(1){
        client_sock_f = accept(serv_sock_f, (struct sockaddr *)&client_addr, &client_sock_l);
        pid_t pid = fork();

        if(pid==0){
            read_header(client_sock_f);
            response(client_sock_f);
            close(client_sock_f) ;
            break;
        }else if(pid>0){
            printf("accept next ... \n");
        }else{
            break;
        }
    }
    */


    exit(0);
}

static void *thread_func(void *udata)
{
	/* 扩展多线程注意：这里应该是每个线程管理不同的epoll，避免并发加锁造成拥塞，影响程序效率 */
	ce_ok = (struct epoll_event *)calloc( EP_LIMIT, sizeof(struct epoll_event));
	int i = 0;
	while(1)
	{
		int isok = epoll_wait(ep_clients, ce_ok, 1, -1) ;
		for( i=0; i<isok; i++)
		{
			if(ce_ok[i].events & EPOLLIN)
			{
				//msg(ce_ok[i].data.fd);
                int client_sock_f = ce_ok[i].data.fd;
                void *frameBuff = malloc(2);
                //int recv( _In_ SOCKET s, _Out_ char *buf, _In_ int len, _In_ int flags);
                recv(client_sock_f, frameBuff, 2, 0);

                unsigned char _infos;
                memcpy(&_infos, frameBuff, 1);

                //0 FIN
                //1-3 RSV1-3
                //4-7 opcode

                unsigned char fin = _infos>>7;
                printf("fin is %u \n", fin);

                unsigned char _len, len_flag;
                memcpy(&_len, frameBuff+1, 1);

                //0 MASK
                //1-7 Payload len

                unsigned long len = _len ;
                len_flag = _len;

                unsigned char mask = _len>>7;
                printf("mask is %u\n", mask);

                unsigned char _mask[4];
                if(mask){
                    len_flag = len = _len^128 ;
                    //recv(client_sock_f, _mask, 4, 0);
                }

                if(len_flag>125){   //暂时不支持大内容数据
                    if (len_flag==126) {
                        // code
                        char xx[2];
                        recv(client_sock_f, xx, 2, 0);
                        unsigned short _len16 ;
                        memcpy(&_len16, xx, 2);
                        len = ntohs(_len16);
                    }else if(len_flag==127){
                        char xx[8];
                        recv(client_sock_f, xx, 8, 0);
                        unsigned long _len64 ;
                        memcpy(&_len64, xx, 8);
                        len = ntohl(_len64);
                    }else{
                        printf("content too long \n");
                        exit(-1);
                    }
                }

                printf("len is %u \n", len);

                if(mask){
                    recv(client_sock_f, _mask, 4, 0);
                }

                char *content = (char *) malloc(len);
                recv(client_sock_f, content, len, 0);

                if(mask){
                    // 解码
                    int i;
                    for(i=0; i<len; i++){
                        content[i] = content[i] ^ _mask[i%4] ;
                    }
                }

                printf("recv: %s\n", content);

                // response
                size_t _send_len = 32*4 + len , send_len = 0  ; // 待发送的数据长度
                void *sendBuff = malloc(_send_len);

                unsigned char response_1b = 0 ;
                response_1b = response_1b | (1<<7) ;
                response_1b = response_1b | 1 ;
                unsigned char response_2b = len_flag;

                memcpy(sendBuff, &response_1b, 1);
                memcpy(sendBuff+1, &response_2b, 1);
                send_len+=2;

                if (len_flag==126) {

                    unsigned short _tmp = htons(len);
                    memcpy(sendBuff+2, &_tmp, 2);
                    send_len += 2;
                }else if(len_flag==127){

                    unsigned long _tmp = htonl(len);
                    memcpy(sendBuff+2, &_tmp, 8);
                    send_len += 8;
                }

                memcpy(sendBuff+send_len, content, len) ;
                send_len += len;
                //send(client_sock_f, sendBuff, send_len, 0);
                size_t i = 0 ;
                for (i = 0; i < cli_counter; i++) {
                    /* code */
                    send(cli_vertor[i], sendBuff, send_len, 0);
                }

                //清理内存 ╭(╯^╰)╮
                free(sendBuff);
                sendBuff = NULL;
                free(content);
                content = NULL;
                free(frameBuff);
                frameBuff = NULL;
			}
		}
	}


	pthread_exit( NULL );
}

void sigfunc_callback(int sig)
{
    switch(sig)
    {
        case SIGCHLD:
            sig_chld_func();
            break;
        case SIGINT:
            sig_int_func();
        default:
            break;
    }
}

void sig_int_func()
{
    printf("close program ! bey-bey \n");
    if(serv_sock_f>0)
        close(serv_sock_f);

    exit(0);
}

void sig_chld_func()
{
    pid_t pid = 0 ;
    int stat = 0 ;
    if((pid = waitpid(-1, &stat, WNOHANG))>0){
          printf("子进程 %d 结束 stat = %d \n", pid, stat);

          if(WIFEXITED(stat))
              printf("子进程正常结束 status %d \n", WEXITSTATUS(stat)) ;
          else if(WIFSIGNALED(stat))
              printf("子进程是因为一个未捕获信号而终止 signal %d \n", WTERMSIG(stat)) ;
          else if(WIFSTOPPED(stat))
              printf("子进程意外终止 child id %d \n", WSTOPSIG(stat)) ;
          else
              printf("unknow \n");

     }

}

void response(int client_sock_f){

    char *format = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: %s\r\n\r\n" ;

    int format_l = strlen(format);
    size_t key_len = strlen(req_info.accept_key);

    size_t _response_l = key_len+format_l+100 ;
    char *response_buf = (char *)malloc( _response_l );
    memset(response_buf, '\0', _response_l);

    // 组装响应数据
    sprintf(response_buf, format, req_info.accept_key);
    printf("%s \n", response_buf);

    int __l = send(client_sock_f, response_buf, strlen(response_buf), 0);


    if(__l<0){

        perror("send") ;
        exit(-1) ;
    }

    printf("send %d \n", __l);
}

void parse_line(const char *str)
{
    if(strncmp("Sec-WebSocket-Key", str, 17)==0){
        //req_info.method = METHOD_GET ;
        char _k[32];
        char _v[1024];

        sscanf(str, "%s %s", _k, _v) ;
        //strncpy(req_info.file, _f, strlen(_f));
        keygen(_v);
    }
}

char* keygen(const char *key)
{
    char *_k = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
    size_t _f_t = strlen(key) + strlen(_k) + 1 ;
    char *mess = (char *)malloc( _f_t ) ;
    memset(mess, '\0', _f_t);

    sprintf(mess, "%s%s", key, _k);
    // 将 SHA-1 加密后的字符串再进行一次 base64 加密
    EVP_MD_CTX *mdctx;
	const EVP_MD *md;
	unsigned char md_value[EVP_MAX_MD_SIZE];
	int md_len;

	OpenSSL_add_all_digests();

	md = EVP_get_digestbyname("sha1");

	mdctx = EVP_MD_CTX_create();
	EVP_DigestInit_ex(mdctx, md, NULL);
	EVP_DigestUpdate(mdctx, mess, strlen(mess));
	EVP_DigestFinal_ex(mdctx, md_value, &md_len);
	EVP_MD_CTX_destroy(mdctx);

	/* Call this once before exit. */
	EVP_cleanup();

    char *result = (char *)malloc(md_len+1);
    memset(result, '\0', md_len+1);
    memcpy(result, md_value, md_len);

    // base64
    // gcc -I./ -L/usr/lib64/openssl/engines/ -lcapi -o demo demo.c base64.c cencode.c cdecode.c
    char* encoded = base64_encode(result);
    printf("Sec-WebSocket-Accept: %s", encoded); /* encoded data has a trailing newline */
    // encoded: Ck1VqNd45QIvq3AZd8XYQLvEhtD/fw==
    free(mess);
    free(result);

    memcpy(req_info.accept_key, encoded, strlen(encoded)-1); // 这个base64包会在最后加一个\n,必须去掉否则客户端会报错
    free(encoded);

    return "Ok";
}

void read_header(int cfd)
{
    int buf_size = BS;
    char *buf = NULL;
    char *line = NULL;

    int i = 0 ;

    int rlen = 0;

    FILE *fp = fdopen(cfd, "r") ;

    HString hs = {NULL, 0};

    buf = (char *)malloc(buf_size+1);

    while(1){

        memset(buf, '\0', buf_size);
        ClearString(&hs);

        while(fgets(buf, buf_size, fp)){
            StrAppend(&hs, buf);
            int _rlen = strlen(buf);
            if(buf[_rlen-1]=='\n')
                break;
        }

        ToString(&hs, &line);

        if(strncmp("\r\n",line,2)==0){
            printf("END \n");
            break;
        }

        if(strlen(line)<=0)
            break;

        printf("%s", line);
        parse_line(line);
    }

    printf("the request info is %s\n", req_info.accept_key);

}
