/*
 * linklist.h
 * 双向链表结构
 *  Created on: 2015年6月22日
 *      Author: lixin
 */

#ifndef LINKLIST_H_
#define LINKLIST_H_

/* 支持自定义类型 */
#ifndef UDF_ELETYPE
typedef int EleType ;
#endif

typedef struct _LNode{
	struct _LNode *prev ;
	struct _LNode *next ;
	EleType data;
} LNode;

typedef struct _LLink{
	struct _LNode *head;
	struct _LNode *tail;
	int len;
} LLink;

/* 初始化链表
返回：1 成功 0 失败 */
int init_llink(LLink *llink) ;

/*链表成员数
返回：int 成员数量 */
int len_llink(LLink *llink) ;

/*是否为空表
返回：1 是 0 否 */
int isempty_llink(LLink *llink) ;

/*添加数据到链表
返回：1 成功 0 失败 */
int append_data_llink(LLink *llink, EleType e) ;

/*从链表中删除数据
返回：int 删除成员数 */
int del_data_llink(LLink *llink, EleType e, int (*equals_callback_llink)(EleType , EleType )) ;

/*遍历链表 */
void foreach_llink(LLink *llink, void (*iterator_callback_llink)(EleType)) ;

#endif /* LINKLIST_H_ */
